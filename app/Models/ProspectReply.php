<?php

namespace App\Models;

use App\Enums\ProspectReplyStatus;
use Database\Factories\ProspectReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property ProspectReplyStatus $status */
#[Fillable(['channel', 'body', 'received_at', 'status', 'provider', 'model', 'prompt', 'intent', 'confidence', 'summary', 'recommended_action', 'suggested_reply', 'should_pause_follow_ups', 'input_tokens', 'output_tokens', 'result', 'error_message', 'started_at', 'analyzed_at'])]
class ProspectReply extends Model
{
    /** @use HasFactory<ProspectReplyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'status' => ProspectReplyStatus::class,
            'should_pause_follow_ups' => 'boolean',
            'result' => 'array',
            'started_at' => 'datetime',
            'analyzed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Prospect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** @return BelongsTo<ProspectOutreach, $this> */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(ProspectOutreach::class, 'prospect_outreach_id');
    }
}
