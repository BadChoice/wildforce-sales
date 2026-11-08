<?php

namespace App\Models;

use App\Enums\ProspectOutreachStatus;
use Database\Factories\ProspectOutreachFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProspectOutreachStatus $status
 */
#[Fillable(['status', 'provider', 'model', 'prompt', 'channel', 'channel_url', 'public_email', 'recipient_name', 'recipient_role', 'language', 'subject', 'message', 'rationale', 'sources', 'input_tokens', 'output_tokens', 'result', 'error_message', 'started_at', 'generated_at', 'approved_by_user_id', 'approved_at', 'sent_at'])]
class ProspectOutreach extends Model
{
    /** @use HasFactory<ProspectOutreachFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ProspectOutreachStatus::class,
            'sources' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Prospect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
