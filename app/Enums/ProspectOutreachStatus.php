<?php

namespace App\Enums;

enum ProspectOutreachStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Draft = 'draft';
    case Approved = 'approved';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Running => 'Running',
            self::Draft => 'Draft ready',
            self::Approved => 'Approved',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Queued => 'amber',
            self::Running => 'blue',
            self::Draft => 'violet',
            self::Approved => 'green',
            self::Sent => 'emerald',
            self::Failed => 'red',
        };
    }
}
