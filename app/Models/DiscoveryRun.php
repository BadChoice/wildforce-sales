<?php

namespace App\Models;

use App\Enums\DiscoveryRunStatus;
use Database\Factories\DiscoveryRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property DiscoveryRunStatus $status
 */
#[Fillable(['status', 'provider', 'model', 'prompt', 'candidate_limit', 'discovered_count', 'input_tokens', 'output_tokens', 'result', 'error_message', 'started_at', 'completed_at'])]
class DiscoveryRun extends Model
{
    /** @use HasFactory<DiscoveryRunFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => DiscoveryRunStatus::class,
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DiscoveryCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DiscoveryCampaign::class, 'discovery_campaign_id');
    }
}
