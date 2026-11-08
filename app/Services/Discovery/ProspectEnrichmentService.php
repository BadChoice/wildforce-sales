<?php

namespace App\Services\Discovery;

use App\Enums\LeadActivityType;
use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectStatus;
use App\Jobs\EnrichProspect;
use App\Models\LeadActivity;
use App\Models\Prospect;
use App\Models\ProspectEnrichment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ProspectEnrichmentService
{
    public function queue(Prospect $prospect): ProspectEnrichment
    {
        /** @var array{0: ProspectEnrichment, 1: bool} $queuedEnrichment */
        $queuedEnrichment = DB::transaction(function () use ($prospect): array {
            $prospect = Prospect::query()->lockForUpdate()->findOrFail($prospect->id);

            $pendingEnrichment = $prospect->enrichments()
                ->whereIn('status', [ProspectEnrichmentStatus::Queued, ProspectEnrichmentStatus::Running])
                ->latest()
                ->first();

            if ($pendingEnrichment !== null) {
                return [$pendingEnrichment, false];
            }

            return [$prospect->enrichments()->create([
                'status' => ProspectEnrichmentStatus::Queued,
                'provider' => config('growth.enrichment.provider'),
                'model' => config('growth.enrichment.model'),
                'prompt' => $this->promptFor($prospect),
            ]), true];
        });

        [$enrichment, $wasQueued] = $queuedEnrichment;

        if ($wasQueued) {
            EnrichProspect::dispatch($enrichment->id)->onQueue('discovery');
        }

        return $enrichment;
    }

    public function markRunning(ProspectEnrichment $enrichment): void
    {
        $enrichment->update([
            'status' => ProspectEnrichmentStatus::Running,
            'started_at' => now(),
            'error_message' => null,
        ]);
    }

    public function complete(ProspectEnrichment $enrichment, StructuredAgentResponse $response): void
    {
        $data = Validator::validate($response->structured, [
            'is_match' => ['required', 'boolean'],
            'website' => ['nullable', 'url', 'max:255'],
            'public_phone' => ['nullable', 'string', 'max:255'],
            'social_profiles' => ['present', 'array'],
            'social_profiles.*' => ['url', 'max:255'],
            'detected_services' => ['present', 'array'],
            'detected_services.*' => ['string', 'max:255'],
            'signals' => ['present', 'array'],
            'signals.*' => ['string', 'max:500'],
            'score' => ['required', 'integer', 'between:0,100'],
            'confidence' => ['required', 'in:low,medium,high'],
            'rationale' => ['required', 'string', 'max:2000'],
            'suggested_angle' => ['nullable', 'string', 'max:2000'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.name' => ['required', 'string', 'max:255'],
            'sources.*.url' => ['required', 'url', 'max:2000'],
            'sources.*.excerpt' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($data, $enrichment, $response): void {
            $prospect = Prospect::query()->lockForUpdate()->findOrFail($enrichment->prospect_id);
            $status = $this->prospectStatus($data);
            $website = $data['website'] ?? $prospect->website;

            $prospect->update([
                'website' => $website,
                'domain' => $this->domainFor($website),
                'public_phone' => $data['public_phone'] ?? $prospect->public_phone,
                'social_profiles' => $data['social_profiles'],
                'status' => $status,
                'score' => $data['score'],
                'confidence' => $data['confidence'],
                'suggested_angle' => $data['suggested_angle'],
                'detected_services' => $data['detected_services'],
                'signals' => $data['signals'],
            ]);

            foreach ($data['sources'] as $source) {
                $prospect->observations()->firstOrCreate([
                    'source_hash' => hash('sha256', $source['url'].'|'.($source['excerpt'] ?? '')),
                ], [
                    'source_name' => $source['name'],
                    'source_url' => $source['url'],
                    'excerpt' => $source['excerpt'] ?? null,
                    'facts' => [
                        'signals' => $data['signals'],
                        'detected_services' => $data['detected_services'],
                    ],
                    'observed_at' => now(),
                ]);
            }

            $prospect->assessments()->create([
                'score' => $data['score'],
                'confidence' => $data['confidence'],
                'rationale' => $data['rationale'],
                'suggested_angle' => $data['suggested_angle'],
                'detected_services' => $data['detected_services'],
                'signals' => $data['signals'],
                'evidence' => $data['sources'],
            ]);

            LeadActivity::create([
                'prospect_id' => $prospect->id,
                'type' => $this->activityType($status),
                'metadata' => ['score' => $data['score'], 'enrichment_id' => $enrichment->id],
                'occurred_at' => now(),
            ]);

            $enrichment->update([
                'status' => ProspectEnrichmentStatus::Completed,
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
                'result' => $data,
                'completed_at' => now(),
            ]);
        });
    }

    public function fail(ProspectEnrichment $enrichment, Throwable $exception): void
    {
        $enrichment->update([
            'status' => ProspectEnrichmentStatus::Failed,
            'error_message' => Str::limit($exception->getMessage(), 5000),
            'completed_at' => now(),
        ]);
    }

    private function promptFor(Prospect $prospect): string
    {
        $prospect->loadMissing('observations');

        return json_encode([
            'task' => 'Enrich and qualify this discovered business for WildForce.',
            'prospect' => [
                'name' => $prospect->name,
                'segment' => $prospect->segment,
                'website' => $prospect->website,
                'city' => $prospect->city,
                'province' => $prospect->province,
                'country' => $prospect->country,
                'known_sources' => $prospect->observations->map(fn ($observation): array => [
                    'name' => $observation->source_name,
                    'url' => $observation->source_url,
                ])->all(),
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string, mixed> $data */
    private function prospectStatus(array $data): ProspectStatus
    {
        if (! $data['is_match']) {
            return ProspectStatus::Rejected;
        }

        return $data['score'] >= config('growth.discovery.qualified_score')
            ? ProspectStatus::Qualified
            : ProspectStatus::Enriched;
    }

    private function activityType(ProspectStatus $status): LeadActivityType
    {
        return match ($status) {
            ProspectStatus::Qualified => LeadActivityType::Qualified,
            ProspectStatus::Rejected => LeadActivityType::Rejected,
            default => LeadActivityType::Enriched,
        };
    }

    private function domainFor(?string $website): ?string
    {
        if ($website === null) {
            return null;
        }

        $host = parse_url($website, PHP_URL_HOST);

        return is_string($host) ? Str::lower(Str::after($host, 'www.')) : null;
    }
}
