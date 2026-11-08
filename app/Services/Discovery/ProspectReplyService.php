<?php

namespace App\Services\Discovery;

use App\Enums\LeadActivityType;
use App\Enums\ProspectReplyStatus;
use App\Jobs\AnalyzeProspectReply;
use App\Models\LeadActivity;
use App\Models\Prospect;
use App\Models\ProspectReply;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ProspectReplyService
{
    /** @param array{channel: string, body: string, received_at: mixed} $data */
    public function store(Prospect $prospect, array $data): ProspectReply
    {
        $reply = $prospect->replies()->create([
            ...$data,
            'status' => ProspectReplyStatus::Queued,
            'provider' => config('growth.reply.provider'),
            'model' => config('growth.reply.model'),
            'prompt' => json_encode(['prospect' => $prospect->only(['name', 'segment', 'suggested_angle']), 'reply' => $data['body']], JSON_THROW_ON_ERROR),
        ]);
        $prospect->update(['replied_at' => now()]);
        LeadActivity::create(['prospect_id' => $prospect->id, 'type' => LeadActivityType::Replied, 'metadata' => ['reply_id' => $reply->id], 'occurred_at' => now()]);
        AnalyzeProspectReply::dispatch($reply->id)->onQueue('discovery');

        return $reply;
    }

    public function complete(ProspectReply $reply, StructuredAgentResponse $response): void
    {
        $data = Validator::validate($response->structured, ['intent' => ['required', 'in:interested,not_interested,question,out_of_office,other'], 'confidence' => ['required', 'in:low,medium,high'], 'summary' => ['required', 'string', 'max:2000'], 'recommended_action' => ['required', 'in:reply,book_demo,pause_follow_ups,close,wait'], 'suggested_reply' => ['nullable', 'string', 'max:4000'], 'should_pause_follow_ups' => ['required', 'boolean']]);
        $reply->update([...$data, 'status' => ProspectReplyStatus::Analyzed, 'result' => $data, 'input_tokens' => $response->usage->inputTokens, 'output_tokens' => $response->usage->outputTokens, 'analyzed_at' => now()]);
    }

    public function fail(ProspectReply $reply, Throwable $exception): void
    {
        $reply->update(['status' => ProspectReplyStatus::Failed, 'error_message' => $exception->getMessage()]);
    }

    public function pauseFollowUps(Prospect $prospect): void
    {
        $prospect->update(['follow_up_paused_at' => now(), 'follow_up_pause_reason' => 'Manually paused']);
        LeadActivity::create(['prospect_id' => $prospect->id, 'type' => LeadActivityType::Suppressed, 'occurred_at' => now()]);
    }
}
