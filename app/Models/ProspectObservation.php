<?php

namespace App\Models;

use Database\Factories\ProspectObservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prospect_id', 'discovery_run_id', 'source_name', 'source_url', 'source_hash', 'excerpt', 'facts', 'observed_at'])]
class ProspectObservation extends Model
{
    /** @use HasFactory<ProspectObservationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'facts' => 'array',
            'observed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Prospect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** @return BelongsTo<DiscoveryRun, $this> */
    public function discoveryRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRun::class);
    }
}
