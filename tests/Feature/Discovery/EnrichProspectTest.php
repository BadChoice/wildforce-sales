<?php

use App\Ai\Agents\ProspectEnrichmentAgent;
use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectOutreachStatus;
use App\Enums\ProspectStatus;
use App\Jobs\EnrichProspect;
use App\Models\Prospect;
use App\Models\ProspectEnrichment;
use App\Models\ProspectOutreach;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('queues prospect enrichment for a growth user', function () {
    Queue::fake([EnrichProspect::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = Prospect::factory()->create(['name' => 'Strength Studio Barcelona']);

    $this->actingAs($user)
        ->post(route('discovery.prospects.enrich', $prospect))
        ->assertRedirect()
        ->assertSessionHas('status', "Prospect enrichment queued for '{$prospect->name}'.");

    $enrichment = ProspectEnrichment::query()->sole();

    expect($enrichment->status)->toBe(ProspectEnrichmentStatus::Queued);
    Queue::assertPushed(EnrichProspect::class, fn (EnrichProspect $job): bool => $job->prospectEnrichmentId === $enrichment->id);
});

it('forbids prospect enrichment for a user without growth access', function () {
    Queue::fake([EnrichProspect::class]);

    $user = User::factory()->create();
    $prospect = Prospect::factory()->create();

    $this->actingAs($user)
        ->post(route('discovery.prospects.enrich', $prospect))
        ->assertForbidden();

    $this->assertDatabaseCount('prospect_enrichments', 0);
    Queue::assertNothingPushed();
});

it('renders the prospect status in the prospects index', function () {
    $user = User::factory()->growthAdmin()->create();
    Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    $this->actingAs($user)
        ->get(route('discovery.prospects.index'))
        ->assertOk()
        ->assertSee('Qualified');
});

it('sorts prospects by every table column', function (string $sort, Closure $createProspects) {
    $user = User::factory()->growthAdmin()->create();
    [$firstProspect, $secondProspect] = $createProspects();

    $this->actingAs($user)
        ->get(route('discovery.prospects.index', ['sort' => $sort, 'direction' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder([$firstProspect->name, $secondProspect->name]);
})->with([
    'business' => ['business', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Alpha Fitness']),
            Prospect::factory()->create(['name' => 'Zulu Fitness']),
        ];
    }],
    'location' => ['location', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Barcelona Fitness', 'city' => 'Barcelona']),
            Prospect::factory()->create(['name' => 'Zaragoza Fitness', 'city' => 'Zaragoza']),
        ];
    }],
    'status' => ['status', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Discovered Fitness', 'status' => ProspectStatus::Discovered]),
            Prospect::factory()->create(['name' => 'Qualified Fitness', 'status' => ProspectStatus::Qualified]),
        ];
    }],
    'enrichment' => ['enrichment', function (): array {
        $completed = Prospect::factory()->create(['name' => 'Completed Enrichment Fitness']);
        $queued = Prospect::factory()->create(['name' => 'Queued Enrichment Fitness']);

        ProspectEnrichment::factory()->for($completed)->create(['status' => ProspectEnrichmentStatus::Completed]);
        ProspectEnrichment::factory()->for($queued)->create(['status' => ProspectEnrichmentStatus::Queued]);

        return [$completed, $queued];
    }],
    'outreach' => ['outreach', function (): array {
        $approved = Prospect::factory()->create(['name' => 'Approved Outreach Fitness']);
        $queued = Prospect::factory()->create(['name' => 'Queued Outreach Fitness']);

        ProspectOutreach::factory()->for($approved)->create(['status' => ProspectOutreachStatus::Approved]);
        ProspectOutreach::factory()->for($queued)->create(['status' => ProspectOutreachStatus::Queued]);

        return [$approved, $queued];
    }],
    'contact' => ['contact', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Not Contacted Fitness']),
            Prospect::factory()->create(['name' => 'Contacted Fitness', 'contacted_at' => now()]),
        ];
    }],
    'progress' => ['progress', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Discovery Progress Fitness', 'status' => ProspectStatus::Discovered]),
            Prospect::factory()->create(['name' => 'Qualified Progress Fitness', 'status' => ProspectStatus::Qualified]),
        ];
    }],
    'fit' => ['fit', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Low Fit Fitness', 'score' => 10]),
            Prospect::factory()->create(['name' => 'High Fit Fitness', 'score' => 90]),
        ];
    }],
    'suggested angle' => ['suggested-angle', function (): array {
        return [
            Prospect::factory()->create(['name' => 'Alpha Angle Fitness', 'suggested_angle' => 'A relevant angle']),
            Prospect::factory()->create(['name' => 'Zulu Angle Fitness', 'suggested_angle' => 'Z relevant angle']),
        ];
    }],
]);

it('sorts prospects in descending order', function () {
    $user = User::factory()->growthAdmin()->create();
    $alpha = Prospect::factory()->create(['name' => 'Alpha Fitness']);
    $zulu = Prospect::factory()->create(['name' => 'Zulu Fitness']);

    $this->actingAs($user)
        ->get(route('discovery.prospects.index', ['sort' => 'business', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder([$zulu->name, $alpha->name]);
});

it('uses a safe default direction for an invalid sort direction', function () {
    $user = User::factory()->growthAdmin()->create();
    $lowFit = Prospect::factory()->create(['name' => 'Low Fit Fitness', 'score' => 10]);
    $highFit = Prospect::factory()->create(['name' => 'High Fit Fitness', 'score' => 90]);

    $this->actingAs($user)
        ->get(route('discovery.prospects.index', ['sort' => 'fit', 'direction' => 'asc; drop table prospects']))
        ->assertOk()
        ->assertSeeInOrder([$highFit->name, $lowFit->name]);
});

it('qualifies a matching prospect after enrichment', function () {
    $prospect = Prospect::factory()->create([
        'name' => 'Strength Studio Barcelona',
        'status' => ProspectStatus::Discovered,
    ]);
    $enrichment = ProspectEnrichment::factory()->for($prospect)->create([
        'prompt' => 'Enrich Strength Studio Barcelona.',
    ]);

    ProspectEnrichmentAgent::fake([enrichmentResponse(isMatch: true, score: 82)]);

    app()->call([new EnrichProspect($enrichment->id), 'handle']);

    $this->assertDatabaseHas('prospects', [
        'id' => $prospect->id,
        'status' => 'qualified',
        'score' => 82,
    ]);
    $this->assertDatabaseHas('prospect_observations', [
        'prospect_id' => $prospect->id,
        'source_name' => 'Strength Studio website',
    ]);
    $this->assertDatabaseHas('prospect_assessments', [
        'prospect_id' => $prospect->id,
        'score' => 82,
    ]);
    $this->assertDatabaseHas('lead_activities', [
        'prospect_id' => $prospect->id,
        'type' => 'qualified',
    ]);

    expect($enrichment->fresh()->status)->toBe(ProspectEnrichmentStatus::Completed);
    ProspectEnrichmentAgent::assertPromptedTimes();
});

it('rejects a prospect that does not match the ICP after enrichment', function () {
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Discovered]);
    $enrichment = ProspectEnrichment::factory()->for($prospect)->create();

    ProspectEnrichmentAgent::fake([enrichmentResponse(isMatch: false, score: 0)]);

    app()->call([new EnrichProspect($enrichment->id), 'handle']);

    $this->assertDatabaseHas('prospects', [
        'id' => $prospect->id,
        'status' => 'rejected',
        'score' => 0,
    ]);
    $this->assertDatabaseHas('lead_activities', [
        'prospect_id' => $prospect->id,
        'type' => 'rejected',
    ]);
});

/** @return array<string, mixed> */
function enrichmentResponse(bool $isMatch, int $score): array
{
    return [
        'is_match' => $isMatch,
        'website' => 'https://strength.example.com',
        'public_phone' => '+34 930 000 000',
        'social_profiles' => ['https://instagram.com/strengthstudio'],
        'detected_services' => ['personal training'],
        'signals' => ['Offers personal training in Barcelona.'],
        'score' => $score,
        'confidence' => 'high',
        'rationale' => $isMatch
            ? 'The business offers personal training in Barcelona.'
            : 'The business does not offer a fitness service.',
        'suggested_angle' => $isMatch ? 'Centralize client plans and follow-up.' : null,
        'sources' => [[
            'name' => 'Strength Studio website',
            'url' => 'https://strength.example.com',
            'excerpt' => 'Personal training in Barcelona.',
        ]],
    ];
}
