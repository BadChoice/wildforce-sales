<?php

namespace App\Services\Discovery;

use App\Enums\LeadActivityType;
use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectOutreachStatus;
use App\Enums\ProspectStatus;
use App\Jobs\GenerateProspectOutreach;
use App\Models\LeadActivity;
use App\Models\Prospect;
use App\Models\ProspectOutreach;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Ai\Responses\StructuredAgentResponse;
use LogicException;
use Throwable;

class ProspectOutreachService
{
    public function queue(Prospect $prospect): ProspectOutreach
    {
        /** @var array{0: ProspectOutreach, 1: bool} $queuedOutreach */
        $queuedOutreach = DB::transaction(function () use ($prospect): array {
            $prospect = Prospect::query()->lockForUpdate()->findOrFail($prospect->id);
            $this->ensureProspectIsReady($prospect);

            $existingOutreach = $prospect->outreaches()
                ->whereNotIn('status', [ProspectOutreachStatus::Failed, ProspectOutreachStatus::Sent, ProspectOutreachStatus::Superseded])
                ->latest()
                ->first();

            if ($existingOutreach !== null) {
                return [$existingOutreach, false];
            }

            return [$prospect->outreaches()->create([
                'status' => ProspectOutreachStatus::Queued,
                'provider' => config('growth.outreach.provider'),
                'model' => config('growth.outreach.model'),
                'prompt' => $this->promptFor($prospect),
            ]), true];
        });

        [$outreach, $wasQueued] = $queuedOutreach;

        if ($wasQueued) {
            GenerateProspectOutreach::dispatch($outreach->id)->onQueue('discovery');
        }

        return $outreach;
    }

    public function markRunning(ProspectOutreach $outreach): void
    {
        $outreach->update([
            'status' => ProspectOutreachStatus::Running,
            'started_at' => now(),
            'error_message' => null,
        ]);
    }

    public function regenerate(ProspectOutreach $outreach): ProspectOutreach
    {
        if ($outreach->status !== ProspectOutreachStatus::Draft) {
            throw new LogicException('Only a draft can be rebuilt.');
        }

        $outreach->update([
            'status' => ProspectOutreachStatus::Superseded,
            'superseded_at' => now(),
        ]);

        return $this->queue($outreach->prospect);
    }

    public function complete(ProspectOutreach $outreach, StructuredAgentResponse $response): void
    {
        $allowedValuePropositionKeys = array_keys($this->valuePropositionsFor($outreach->prospect));

        $data = Validator::validate($response->structured, [
            'channel' => ['required', 'in:email,contact_form,instagram,linkedin,website'],
            'channel_url' => ['nullable', 'url', 'max:2000'],
            'public_email' => ['nullable', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_role' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'in:ca,es'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
            'rationale' => ['required', 'string', 'max:2000'],
            'selected_value_proposition_keys' => ['present', 'array', 'max:2'],
            'selected_value_proposition_keys.*' => ['string', Rule::in($allowedValuePropositionKeys)],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.name' => ['required', 'string', 'max:255'],
            'sources.*.url' => ['required', 'url', 'max:2000'],
            'sources.*.excerpt' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($data, $outreach, $response): void {
            $outreach->update([
                'status' => ProspectOutreachStatus::Draft,
                'channel' => $data['channel'],
                'channel_url' => $data['channel_url'],
                'public_email' => $data['public_email'],
                'recipient_name' => $data['recipient_name'],
                'recipient_role' => $data['recipient_role'],
                'language' => $data['language'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'rationale' => $data['rationale'],
                'value_proposition_keys' => $data['selected_value_proposition_keys'],
                'sources' => $data['sources'],
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
                'result' => $data,
                'generated_at' => now(),
            ]);
        });
    }

    public function approve(ProspectOutreach $outreach, User $user): void
    {
        if ($outreach->status !== ProspectOutreachStatus::Draft) {
            throw new LogicException('Only a prepared outreach draft can be approved.');
        }

        $outreach->update([
            'status' => ProspectOutreachStatus::Approved,
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function markSent(ProspectOutreach $outreach): void
    {
        if ($outreach->status !== ProspectOutreachStatus::Approved) {
            throw new LogicException('Only an approved outreach draft can be marked as sent.');
        }

        $outreach->update([
            'status' => ProspectOutreachStatus::Sent,
            'sent_at' => now(),
        ]);

        $outreach->prospect()->update(['contacted_at' => now()]);

        LeadActivity::create([
            'prospect_id' => $outreach->prospect_id,
            'type' => LeadActivityType::Contacted,
            'metadata' => ['outreach_id' => $outreach->id, 'channel' => $outreach->channel],
            'occurred_at' => now(),
        ]);
    }

    public function fail(ProspectOutreach $outreach, Throwable $exception): void
    {
        $outreach->update([
            'status' => ProspectOutreachStatus::Failed,
            'error_message' => Str::limit($exception->getMessage(), 5000),
        ]);
    }

    private function ensureProspectIsReady(Prospect $prospect): void
    {
        $latestEnrichment = $prospect->latestEnrichment()->first();

        if ($prospect->status !== ProspectStatus::Qualified || $latestEnrichment?->status !== ProspectEnrichmentStatus::Completed) {
            throw new LogicException('Only a qualified prospect with completed enrichment can receive an outreach draft.');
        }
    }

    private function promptFor(Prospect $prospect): string
    {
        $prospect->loadMissing('observations');

        return json_encode([
            'task' => 'Find the best public professional channel and draft a first outreach message for this qualified prospect.',
            'prospect' => [
                'name' => $prospect->name,
                'segment' => $prospect->segment,
                'website' => $prospect->website,
                'city' => $prospect->city,
                'province' => $prospect->province,
                'country' => $prospect->country,
                'preferred_language' => $prospect->outreachLanguage(),
                'score' => $prospect->score,
                'suggested_angle' => $prospect->suggested_angle,
                'signals' => $prospect->signals,
                'value_propositions' => $this->valuePropositionsFor($prospect),
                'latest_assessment' => $prospect->assessments()->latest()->value('rationale'),
                'known_sources' => $prospect->observations->map(fn ($observation): array => [
                    'name' => $observation->source_name,
                    'url' => $observation->source_url,
                ])->all(),
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, array<string, mixed>> */
    private function valuePropositionsFor(Prospect $prospect): array
    {
        return array_filter(
            config('growth.outreach.value_propositions', []),
            fn (array $proposition): bool => in_array($prospect->segment, $proposition['segments'], true),
        );
    }
}
