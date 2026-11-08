<?php

namespace App\Models;

use Database\Factories\ProspectAssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prospect_id', 'discovery_run_id', 'score', 'confidence', 'rationale', 'suggested_angle', 'detected_services', 'signals', 'evidence'])]
class ProspectAssessment extends Model
{
    /** @use HasFactory<ProspectAssessmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'detected_services' => 'array',
            'signals' => 'array',
            'evidence' => 'array',
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
