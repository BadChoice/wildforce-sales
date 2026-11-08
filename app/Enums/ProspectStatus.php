<?php

namespace App\Enums;

enum ProspectStatus: string
{
    case Discovered = 'discovered';
    case Enriched = 'enriched';
    case Qualified = 'qualified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Discovered => 'Discovered',
            self::Enriched => 'Enriched',
            self::Qualified => 'Qualified',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Discovered => 'zinc',
            self::Enriched => 'blue',
            self::Qualified => 'green',
            self::Rejected => 'red',
        };
    }
}
