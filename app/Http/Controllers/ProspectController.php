<?php

namespace App\Http\Controllers;

use App\Enums\ProspectStatus;
use App\Http\Requests\StoreProspectReplyRequest;
use App\Models\Prospect;
use App\Models\ProspectOutreach;
use App\Models\User;
use App\Services\Discovery\ProspectEnrichmentService;
use App\Services\Discovery\ProspectOutreachService;
use App\Services\Discovery\ProspectReplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class ProspectController extends Controller
{
    public function index(Request $request): View
    {
        $status = ProspectStatus::tryFrom($request->string('status')->toString());
        $search = $request->string('search')->trim()->toString();

        $prospects = Prospect::query()
            ->with(['latestEnrichment', 'latestOutreach', 'latestReply'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('discovery.prospects.index', compact('prospects', 'search', 'status'));
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
}
