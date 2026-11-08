<?php

namespace App\Enums;

enum ProspectEnrichmentStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Running => 'Running',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Queued => 'amber',
            self::Running => 'blue',
            self::Completed => 'green',
            self::Failed => 'red',
        };
    }
}
