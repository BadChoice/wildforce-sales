<?php

namespace App\Enums;

enum LeadActivityType: string
{
    case Discovered = 'discovered';
    case Enriched = 'enriched';
    case Qualified = 'qualified';
    case Rejected = 'rejected';
    case Contacted = 'contacted';
    case Replied = 'replied';
    case DemoRequested = 'demo_requested';
    case Converted = 'converted';
    case Suppressed = 'suppressed';
}
