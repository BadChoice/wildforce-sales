<?php

namespace App\Enums;

enum DiscoveryCampaignStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
}
