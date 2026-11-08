<?php

use App\Ai\Agents\ProspectEnrichmentAgent;
use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectStatus;
use App\Jobs\EnrichProspect;
use App\Models\Prospect;
use App\Models\ProspectEnrichment;
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
