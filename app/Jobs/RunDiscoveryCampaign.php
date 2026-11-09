<?php

namespace App\Jobs;

use App\Ai\Agents\ProspectDiscoveryAgent;
use App\Enums\DiscoveryRunStatus;
use App\Mail\DiscoveryCompleted;
use App\Models\DiscoveryRun;
use App\Services\Discovery\DiscoveryRunService;
use App\Services\Discovery\ProspectDiscoveryService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class RunDiscoveryCampaign implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $discoveryRunId) {}

    public function uniqueId(): string
    {
        return (string) $this->discoveryRunId;
    }

    public function handle(
        ProspectDiscoveryAgent $agent,
        DiscoveryRunService $runs,
        ProspectDiscoveryService $prospects,
    ): void {
        $run = DiscoveryRun::query()->with('campaign')->findOrFail($this->discoveryRunId);

        if ($run->status !== DiscoveryRunStatus::Queued) {
            return;
        }

        $runs->markRunning($run);

        $response = $agent->prompt(
            $run->prompt,
            provider: $run->provider,
            model: $run->model,
            timeout: config('growth.discovery.timeout'),
        );

        $candidates = $response->structured['candidates'] ?? [];

        if (! is_array($candidates)) {
            throw new \RuntimeException('The discovery agent returned an invalid candidate list.');
        }

        try {
            $discoveredCount = $prospects->storeCandidates(
                $run,
                array_slice($candidates, 0, $run->candidate_limit),
            );
        } catch (ValidationException $exception) {
            $runs->fail($run, $exception);

            return;
        }

        $runs->complete($run->fresh('campaign'), $response, $discoveredCount);

        Mail::to((string) config('growth.discovery.report_recipient'))->queue(
            (new DiscoveryCompleted($run->fresh('campaign')))->afterCommit(),
        );
    }

    public function failed(Throwable $exception): void
    {
        $run = DiscoveryRun::query()->find($this->discoveryRunId);

        if ($run !== null) {
            app(DiscoveryRunService::class)->fail($run, $exception);
        }
    }
}
