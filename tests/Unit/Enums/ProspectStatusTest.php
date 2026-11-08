<?php

use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectStatus;

it('maps prospect statuses to clear labels and badge colors', function () {
    expect(ProspectStatus::Discovered->label())->toBe('Discovered')
        ->and(ProspectStatus::Discovered->badgeColor())->toBe('zinc')
        ->and(ProspectStatus::Enriched->badgeColor())->toBe('blue')
        ->and(ProspectStatus::Qualified->badgeColor())->toBe('green')
        ->and(ProspectStatus::Rejected->badgeColor())->toBe('red');
});

it('maps enrichment execution statuses to clear labels and badge colors', function () {
    expect(ProspectEnrichmentStatus::Queued->badgeColor())->toBe('amber')
        ->and(ProspectEnrichmentStatus::Running->badgeColor())->toBe('blue')
        ->and(ProspectEnrichmentStatus::Completed->badgeColor())->toBe('green')
        ->and(ProspectEnrichmentStatus::Failed->badgeColor())->toBe('red');
});
