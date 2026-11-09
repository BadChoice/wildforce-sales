<?php

namespace App\Http\Controllers;

use App\Enums\ProspectStatus;
use App\Http\Requests\DisqualifyProspectRequest;
use App\Http\Requests\RegenerateProspectOutreachRequest;
use App\Http\Requests\StoreProspectReplyRequest;
use App\Http\Requests\UpdateProspectLanguageRequest;
use App\Models\Prospect;
use App\Models\ProspectOutreach;
use App\Models\User;
use App\Services\Discovery\ProspectEnrichmentService;
use App\Services\Discovery\ProspectOutreachService;
use App\Services\Discovery\ProspectReplyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;
use SortDirection;

class ProspectController extends Controller
{
    /** @var array<string, string> */
    private const SORTABLE_COLUMNS = [
        'business' => 'name',
        'location' => 'city',
        'stage' => 'stage',
        'fit' => 'score',
        'suggested-angle' => 'suggested_angle',
    ];

    public function index(Request $request): View
    {
        $status = ProspectStatus::tryFrom($request->string('status')->toString());
        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort')->toString();
        $sort = array_key_exists($sort, self::SORTABLE_COLUMNS) ? $sort : 'fit';
        $direction = $request->string('direction')->toString() === 'asc'
            ? SortDirection::Ascending
            : SortDirection::Descending;

        $prospectsQuery = Prospect::query()
            ->with(['latestEnrichment', 'latestOutreach', 'latestReply'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        $this->applySort($prospectsQuery, $sort, $direction);

        $prospects = $prospectsQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('discovery.prospects.index', compact('direction', 'prospects', 'search', 'sort', 'status'));
    }

    /** @param Builder<Prospect> $prospectsQuery */
    private function applySort(Builder $prospectsQuery, string $sort, SortDirection $direction): void
    {
        match ($sort) {
            'location' => $prospectsQuery->orderBy('city', $direction)->orderBy('province', $direction),
            'stage' => $this->applyStageSort($prospectsQuery, $direction),
            default => $prospectsQuery->orderBy(self::SORTABLE_COLUMNS[$sort], $direction),
        };
    }

    /** @param Builder<Prospect> $prospectsQuery */
    private function applyStageSort(Builder $prospectsQuery, SortDirection $direction): void
    {
        $bindings = [
            ProspectStatus::Qualified->value,
            ProspectStatus::Enriched->value,
            ProspectStatus::Rejected->value,
        ];

        if ($direction === SortDirection::Ascending) {
            $prospectsQuery->orderByRaw(<<<'SQL'
                case
                    when replied_at is not null then 100
                    when contacted_at is not null then 90
                    when exists (select 1 from prospect_outreaches where prospect_outreaches.prospect_id = prospects.id) then 75
                    when status = ? then 60
                    when status = ? then 45
                    when status = ? then 100
                    else 25
                end asc
            SQL, $bindings);

            return;
        }

        $prospectsQuery->orderByRaw(<<<'SQL'
            case
                when replied_at is not null then 100
                when contacted_at is not null then 90
                when exists (select 1 from prospect_outreaches where prospect_outreaches.prospect_id = prospects.id) then 75
                when status = ? then 60
                when status = ? then 45
                when status = ? then 100
                else 25
            end desc
        SQL, $bindings);
    }

    public function show(Prospect $prospect): View
    {
        $prospect->load([
            'observations' => fn ($query) => $query->latest('observed_at'),
            'assessments' => fn ($query) => $query->latest(),
            'activities' => fn ($query) => $query->latest('occurred_at'),
            'latestEnrichment',
            'latestOutreach',
            'latestReply',
        ]);

        return view('discovery.prospects.show', compact('prospect'));
    }

    public function enrich(Prospect $prospect, ProspectEnrichmentService $enrichments): RedirectResponse
    {
        $enrichments->queue($prospect);

        return back()->with('status', "Prospect enrichment queued for '{$prospect->name}'.");
    }

    public function generateOutreach(Prospect $prospect, ProspectOutreachService $outreaches): RedirectResponse
    {
        try {
            $outreaches->queue($prospect);
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Outreach draft queued for '{$prospect->name}'.");
    }

    public function regenerateOutreach(RegenerateProspectOutreachRequest $request, ProspectOutreach $outreach, ProspectOutreachService $outreaches): RedirectResponse
    {
        try {
            $messageGuidance = $request->string('message_guidance')->trim()->toString();
            $outreaches->regenerate($outreach, $messageGuidance !== '' ? $messageGuidance : null);
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Outreach draft rebuild queued.');
    }

    public function approveOutreach(Request $request, ProspectOutreach $outreach, ProspectOutreachService $outreaches): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $outreaches->approve($outreach, $user);
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Outreach draft approved for manual sending.');
    }

    public function markOutreachSent(ProspectOutreach $outreach, ProspectOutreachService $outreaches): RedirectResponse
    {
        try {
            $outreaches->markSent($outreach);
        } catch (LogicException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Outreach marked as sent.');
    }

    public function storeReply(StoreProspectReplyRequest $request, Prospect $prospect, ProspectReplyService $replies): RedirectResponse
    {
        $replies->store($prospect, [
            'channel' => $request->string('channel')->toString(),
            'body' => $request->string('body')->toString(),
            'received_at' => $request->date('received_at') ?? now(),
        ]);

        return back()->with('status', "Reply queued for analysis for '{$prospect->name}'.");
    }

    public function pauseFollowUps(Prospect $prospect, ProspectReplyService $replies): RedirectResponse
    {
        $replies->pauseFollowUps($prospect);

        return back()->with('status', 'Future follow-ups paused.');
    }

    public function updateLanguage(UpdateProspectLanguageRequest $request, Prospect $prospect): RedirectResponse
    {
        $prospect->update($request->validated());

        return back()->with('status', "Outreach language set to {$prospect->preferred_language}.");
    }

    public function disqualify(DisqualifyProspectRequest $request, Prospect $prospect): RedirectResponse
    {
        $prospect->update([
            'status' => ProspectStatus::Rejected,
            'disqualification_reason' => $request->string('disqualification_reason')->toString(),
            'follow_up_paused_at' => now(),
            'follow_up_pause_reason' => 'Disqualified',
        ]);

        return back()->with('status', 'Prospect disqualified and follow-ups paused.');
    }
}
