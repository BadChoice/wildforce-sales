<?php

namespace App\Models;

use App\Enums\ProspectStatus;
use Database\Factories\ProspectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property ProspectStatus $status
 */
#[Fillable(['canonical_key', 'name', 'segment', 'website', 'domain', 'city', 'province', 'country', 'preferred_language', 'public_phone', 'social_profiles', 'status', 'score', 'confidence', 'suggested_angle', 'detected_services', 'signals', 'last_discovered_at', 'contacted_at', 'replied_at', 'follow_up_paused_at', 'follow_up_pause_reason', 'disqualification_reason'])]
class Prospect extends Model
{
    /** @use HasFactory<ProspectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'social_profiles' => 'array',
            'status' => ProspectStatus::class,
            'detected_services' => 'array',
            'signals' => 'array',
            'last_discovered_at' => 'datetime',
            'contacted_at' => 'datetime',
            'replied_at' => 'datetime',
            'follow_up_paused_at' => 'datetime',
        ];
    }

    /** @return HasMany<ProspectObservation, $this> */
    public function observations(): HasMany
    {
        return $this->hasMany(ProspectObservation::class);
    }

    /** @return HasMany<ProspectAssessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(ProspectAssessment::class);
    }

    /** @return HasMany<ProspectContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(ProspectContact::class);
    }

    /** @return HasMany<LeadActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    /** @return HasMany<ProspectEnrichment, $this> */
    public function enrichments(): HasMany
    {
        return $this->hasMany(ProspectEnrichment::class);
    }

    /** @return HasOne<ProspectEnrichment, $this> */
    public function latestEnrichment(): HasOne
    {
        return $this->hasOne(ProspectEnrichment::class)->latestOfMany();
    }

    /** @return HasMany<ProspectOutreach, $this> */
    public function outreaches(): HasMany
    {
        return $this->hasMany(ProspectOutreach::class);
    }

    /** @return HasOne<ProspectOutreach, $this> */
    public function latestOutreach(): HasOne
    {
        return $this->hasOne(ProspectOutreach::class)->latestOfMany();
    }

    /** @return HasMany<ProspectReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(ProspectReply::class);
    }

    /** @return HasOne<ProspectReply, $this> */
    public function latestReply(): HasOne
    {
        return $this->hasOne(ProspectReply::class)->latestOfMany();
    }

    public function pipelineProgress(): int
    {
        if ($this->replied_at !== null) {
            return 100;
        }

        if ($this->contacted_at !== null) {
            return 90;
        }

        if ($this->latestOutreach !== null) {
            return 75;
        }

        return match ($this->status) {
            ProspectStatus::Qualified => 60,
            ProspectStatus::Enriched => 45,
            ProspectStatus::Rejected => 100,
            default => 25,
        };
    }

    public function nextStepLabel(): string
    {
        if ($this->replied_at !== null) {
            return 'Action needed';
        }

        if ($this->contacted_at !== null) {
            return 'Waiting for reply';
        }

        if ($this->latestOutreach?->status?->value === 'draft' || $this->latestOutreach?->status?->value === 'approved') {
            return 'Action needed';
        }

        return match ($this->status) {
            ProspectStatus::Discovered => 'Enrichment needed',
            ProspectStatus::Qualified => 'Outreach needed',
            ProspectStatus::Rejected => 'Closed',
            default => 'Review needed',
        };
    }

    public function nextStepColor(): string
    {
        return match ($this->nextStepLabel()) {
            'Waiting for reply' => 'blue',
            'Closed' => 'red',
            default => 'amber',
        };
    }

    public function outreachLanguage(): string
    {
        if ($this->preferred_language !== null) {
            return $this->preferred_language;
        }

        return in_array($this->province, ['Barcelona', 'Girona', 'Lleida', 'Tarragona'], true) ? 'ca' : 'es';
    }
}
