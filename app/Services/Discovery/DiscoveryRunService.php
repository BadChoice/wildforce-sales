<?php

namespace App\Services\Discovery;

use App\Enums\DiscoveryRunStatus;
use App\Jobs\RunDiscoveryCampaign;
use App\Models\DiscoveryCampaign;
use App\Models\DiscoveryRun;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class DiscoveryRunService
{
    public function queue(DiscoveryCampaign $campaign): DiscoveryRun
    {
        $run = $campaign->runs()->create([
            'status' => DiscoveryRunStatus::Queued,
            'provider' => config('growth.discovery.provider'),
            'model' => config('growth.discovery.model'),
            'prompt' => $this->promptFor($campaign),
            'candidate_limit' => $campaign->daily_candidate_limit,
        ]);

        RunDiscoveryCampaign::dispatch($run->id)->onQueue('discovery');

        return $run;
    }

    public function markRunning(DiscoveryRun $run): void
    {
        $run->update([
            'status' => DiscoveryRunStatus::Running,
            'started_at' => now(),
            'error_message' => null,
        ]);
    }

    public function complete(DiscoveryRun $run, StructuredAgentResponse $response, int $discoveredCount): void
    {
        $run->update([
            'status' => DiscoveryRunStatus::Completed,
            'discovered_count' => $discoveredCount,
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'result' => $response->structured,
            'completed_at' => now(),
        ]);

        $run->campaign->update(['last_run_at' => now()]);
    }

    public function fail(DiscoveryRun $run, Throwable $exception): void
    {
        $run->update([
            'status' => DiscoveryRunStatus::Failed,
            'error_message' => Str::limit($exception->getMessage(), 5000),
            'completed_at' => now(),
        ]);
    }

    private function promptFor(DiscoveryCampaign $campaign): string
    {
        return json_encode([
            'task' => 'Discover businesses that are a strong fit for WildForce.',
            'segments' => $campaign->segments,
            'locations' => $campaign->locations,
            'niches' => $campaign->niches ?? [],
            'language' => $campaign->language,
            'maximum_candidates' => $campaign->daily_candidate_limit,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
