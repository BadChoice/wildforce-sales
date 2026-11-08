<?php

namespace App\Jobs;

use App\Ai\Agents\ProspectReplyAgent;
use App\Enums\ProspectReplyStatus;
use App\Models\ProspectReply;
use App\Services\Discovery\ProspectReplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AnalyzeProspectReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $prospectReplyId) {}

    /**
     * Execute the job.
     */
    public function handle(ProspectReplyAgent $agent, ProspectReplyService $replies): void
    {
        $reply = ProspectReply::query()->findOrFail($this->prospectReplyId);
        if ($reply->status !== ProspectReplyStatus::Queued) {
            return;
        }
        $reply->update(['status' => ProspectReplyStatus::Running, 'started_at' => now()]);
        try {
            $replies->complete($reply, $agent->prompt($reply->prompt, provider: $reply->provider, model: $reply->model, timeout: config('growth.reply.timeout')));
        } catch (Throwable $exception) {
            $replies->fail($reply, $exception);
        }
    }
}
