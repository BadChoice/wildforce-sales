<?php

namespace App\Jobs;

use App\Ai\Agents\ProspectOutreachAgent;
use App\Enums\ProspectOutreachStatus;
use App\Models\ProspectOutreach;
use App\Services\Discovery\ProspectOutreachService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateProspectOutreach implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $prospectOutreachId) {}

    public function uniqueId(): string
    {
        return (string) $this->prospectOutreachId;
    }

    /**
     * Execute the job.
     */
    public function handle(ProspectOutreachAgent $agent, ProspectOutreachService $outreaches): void
    {
        $outreach = ProspectOutreach::query()->findOrFail($this->prospectOutreachId);

        if ($outreach->status !== ProspectOutreachStatus::Queued) {
            return;
        }

        $outreaches->markRunning($outreach);

        $response = $agent->prompt(
            $outreach->prompt,
            provider: $outreach->provider,
            model: $outreach->model,
            timeout: config('growth.outreach.timeout'),
        );

        try {
            $outreaches->complete($outreach, $response);
        } catch (Throwable $exception) {
            $outreaches->fail($outreach, $exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        $outreach = ProspectOutreach::query()->find($this->prospectOutreachId);

        if ($outreach !== null) {
            app(ProspectOutreachService::class)->fail($outreach, $exception);
        }
    }
}
