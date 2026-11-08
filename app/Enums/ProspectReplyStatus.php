<?php

namespace App\Enums;

enum ProspectReplyStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Analyzed = 'analyzed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Running => 'Analyzing',
            self::Analyzed => 'Action needed',
            self::Failed => 'Analysis failed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Queued => 'amber',
            self::Running => 'blue',
            self::Analyzed => 'violet',
            self::Failed => 'red',
        };
    }
}
