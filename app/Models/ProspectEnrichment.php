<?php

namespace App\Models;

use App\Enums\ProspectEnrichmentStatus;
use Database\Factories\ProspectEnrichmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProspectEnrichmentStatus $status
 */
#[Fillable(['status', 'provider', 'model', 'prompt', 'input_tokens', 'output_tokens', 'result', 'error_message', 'started_at', 'completed_at'])]
class ProspectEnrichment extends Model
{
    /** @use HasFactory<ProspectEnrichmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ProspectEnrichmentStatus::class,
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Prospect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}
