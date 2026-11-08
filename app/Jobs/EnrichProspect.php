<?php

namespace App\Jobs;

use App\Ai\Agents\ProspectEnrichmentAgent;
use App\Enums\ProspectEnrichmentStatus;
use App\Models\ProspectEnrichment;
use App\Services\Discovery\ProspectEnrichmentService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Validation\ValidationException;
use Throwable;

class EnrichProspect implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $prospectEnrichmentId) {}

    public function uniqueId(): string
    {
        return (string) $this->prospectEnrichmentId;
    }

    /**
     * Execute the job.
     */
    public function handle(ProspectEnrichmentAgent $agent, ProspectEnrichmentService $enrichments): void
    {
        $enrichment = ProspectEnrichment::query()->findOrFail($this->prospectEnrichmentId);

        if ($enrichment->status !== ProspectEnrichmentStatus::Queued) {
            return;
        }

        $enrichments->markRunning($enrichment);

        $response = $agent->prompt(
            $enrichment->prompt,
            provider: $enrichment->provider,
            model: $enrichment->model,
            timeout: config('growth.enrichment.timeout'),
        );

        try {
            $enrichments->complete($enrichment, $response);
        } catch (ValidationException $exception) {
            $enrichments->fail($enrichment, $exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        $enrichment = ProspectEnrichment::query()->find($this->prospectEnrichmentId);

        if ($enrichment !== null) {
            app(ProspectEnrichmentService::class)->fail($enrichment, $exception);
        }
    }
}
