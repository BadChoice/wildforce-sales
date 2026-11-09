<?php

namespace App\Enums;

enum ProspectPipelineStage: string
{
    case Discovered = 'discovered';
    case EnrichmentInProgress = 'enrichment_in_progress';
    case EnrichmentFailed = 'enrichment_failed';
    case Qualified = 'qualified';
    case OutreachInProgress = 'outreach_in_progress';
    case OutreachFailed = 'outreach_failed';
    case DraftReady = 'draft_ready';
    case ReadyToSend = 'ready_to_send';
    case Contacted = 'contacted';
    case FollowUpRequired = 'follow_up_required';
    case Replied = 'replied';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Discovered => 'Discovered',
            self::EnrichmentInProgress => 'Enrichment in progress',
            self::EnrichmentFailed => 'Enrichment failed',
            self::Qualified => 'Qualified',
            self::OutreachInProgress => 'Generating outreach',
            self::OutreachFailed => 'Outreach failed',
            self::DraftReady => 'Draft ready',
            self::ReadyToSend => 'Ready to send',
            self::Contacted => 'Contacted',
            self::FollowUpRequired => 'Follow up required',
            self::Replied => 'Replied',
            self::Disqualified => 'Disqualified',
        };
    }

    public function detail(): string
    {
        return match ($this) {
            self::Discovered => 'Needs enrichment before qualification.',
            self::EnrichmentInProgress => 'The research agent is still working.',
            self::EnrichmentFailed => 'Retry enrichment to continue.',
            self::Qualified => 'Ready to generate an outreach draft.',
            self::OutreachInProgress => 'The outreach agent is preparing a draft.',
            self::OutreachFailed => 'Rebuild the outreach draft to retry.',
            self::DraftReady => 'Review, rebuild, or approve the draft.',
            self::ReadyToSend => 'The approved message is ready for manual sending.',
            self::Contacted => 'Waiting for a reply.',
            self::FollowUpRequired => 'No reply after three business days; send a follow-up.',
            self::Replied => 'A reply needs review and a next action.',
            self::Disqualified => 'Not a fit; follow-ups are paused.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Discovered => 'zinc',
            self::EnrichmentInProgress, self::OutreachInProgress => 'blue',
            self::Qualified => 'emerald',
            self::DraftReady => 'violet',
            self::ReadyToSend => 'indigo',
            self::Contacted => 'sky',
            self::FollowUpRequired => 'amber',
            self::EnrichmentFailed, self::OutreachFailed, self::Disqualified => 'red',
            self::Replied => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Discovered => 'magnifying-glass',
            self::EnrichmentInProgress, self::OutreachInProgress => 'arrow-path',
            self::EnrichmentFailed, self::OutreachFailed => 'exclamation-circle',
            self::Qualified => 'check-circle',
            self::DraftReady => 'document-text',
            self::ReadyToSend, self::Contacted, self::FollowUpRequired => 'paper-airplane',
            self::Replied => 'chat-bubble-left',
            self::Disqualified => 'x-circle',
        };
    }

    public function progress(): int
    {
        return match ($this) {
            self::Discovered, self::EnrichmentInProgress, self::EnrichmentFailed => 25,
            self::Qualified => 50,
            self::OutreachInProgress, self::OutreachFailed => 60,
            self::DraftReady => 70,
            self::ReadyToSend => 80,
            self::Contacted => 90,
            self::FollowUpRequired => 95,
            self::Replied, self::Disqualified => 100,
        };
    }
}
