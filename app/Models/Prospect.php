<?php

namespace App\Models;

use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectOutreachStatus;
use App\Enums\ProspectPipelineStage;
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

    public function pipelineStage(): ProspectPipelineStage
    {
        if ($this->status === ProspectStatus::Rejected) {
            return ProspectPipelineStage::Disqualified;
        }

        if ($this->replied_at !== null) {
            return ProspectPipelineStage::Replied;
        }

        if ($this->contacted_at !== null) {
            return ProspectPipelineStage::Contacted;
        }

        return match ($this->latestOutreach?->status) {
            ProspectOutreachStatus::Queued, ProspectOutreachStatus::Running => ProspectPipelineStage::OutreachInProgress,
            ProspectOutreachStatus::Failed => ProspectPipelineStage::OutreachFailed,
            ProspectOutreachStatus::Draft => ProspectPipelineStage::DraftReady,
            ProspectOutreachStatus::Approved => ProspectPipelineStage::ReadyToSend,
            default => $this->stageBeforeOutreach(),
        };
    }

    public function pipelineTooltip(): string
    {
        $stage = $this->pipelineStage();
        $enrichment = $this->latestEnrichment?->status?->label() ?? 'Not started';
        $outreach = $this->latestOutreach?->status?->label() ?? 'Not started';
        $contact = match (true) {
            $this->replied_at !== null => 'Replied',
            $this->contacted_at !== null => 'Contacted',
            default => 'Not contacted',
        };

        return "{$stage->detail()} Status: {$this->status->label()} · Enrichment: {$enrichment} · Outreach: {$outreach} · Contact: {$contact}";
    }

    private function stageBeforeOutreach(): ProspectPipelineStage
    {
        return match ($this->status) {
            ProspectStatus::Qualified => ProspectPipelineStage::Qualified,
            ProspectStatus::Enriched => ProspectPipelineStage::Qualified,
            default => match ($this->latestEnrichment?->status) {
                ProspectEnrichmentStatus::Queued, ProspectEnrichmentStatus::Running => ProspectPipelineStage::EnrichmentInProgress,
                ProspectEnrichmentStatus::Failed => ProspectPipelineStage::EnrichmentFailed,
                default => ProspectPipelineStage::Discovered,
            },
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
