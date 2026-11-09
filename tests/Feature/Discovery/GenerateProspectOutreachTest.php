<?php

use App\Ai\Agents\ProspectOutreachAgent;
use App\Enums\ProspectEnrichmentStatus;
use App\Enums\ProspectOutreachStatus;
use App\Enums\ProspectStatus;
use App\Jobs\GenerateProspectOutreach;
use App\Models\Prospect;
use App\Models\ProspectEnrichment;
use App\Models\ProspectOutreach;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('queues outreach generation for a fully qualified prospect', function () {
    Queue::fake([GenerateProspectOutreach::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = readyForOutreach();

    $this->actingAs($user)
        ->post(route('discovery.prospects.outreach.generate', $prospect))
        ->assertRedirect()
        ->assertSessionHas('status', "Outreach draft queued for '{$prospect->name}'.");

    $outreach = ProspectOutreach::query()->sole();

    expect($outreach->status)->toBe(ProspectOutreachStatus::Queued);
    Queue::assertPushed(GenerateProspectOutreach::class, fn (GenerateProspectOutreach $job): bool => $job->prospectOutreachId === $outreach->id);
});

it('does not queue outreach generation until the prospect is ready', function () {
    Queue::fake([GenerateProspectOutreach::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Discovered]);

    $this->actingAs($user)
        ->post(route('discovery.prospects.outreach.generate', $prospect))
        ->assertRedirect()
        ->assertSessionHas('error', 'Only a qualified prospect with completed enrichment can receive an outreach draft.');

    $this->assertDatabaseCount('prospect_outreaches', 0);
    Queue::assertNothingPushed();
});

it('queues a new outreach draft after a previous generation failed', function () {
    Queue::fake([GenerateProspectOutreach::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = readyForOutreach();
    ProspectOutreach::factory()->for($prospect)->create([
        'status' => ProspectOutreachStatus::Failed,
        'error_message' => 'The model returned an invalid channel.',
    ]);

    $this->actingAs($user)
        ->post(route('discovery.prospects.outreach.generate', $prospect))
        ->assertRedirect();

    $this->assertDatabaseCount('prospect_outreaches', 2);
    $this->assertDatabaseHas('prospect_outreaches', [
        'prospect_id' => $prospect->id,
        'status' => 'queued',
    ]);
    Queue::assertPushed(GenerateProspectOutreach::class);
});

it('rebuilds a draft while preserving the previous version', function () {
    Queue::fake([GenerateProspectOutreach::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = readyForOutreach();
    $outreach = ProspectOutreach::factory()->for($prospect)->create([
        'status' => ProspectOutreachStatus::Draft,
        'message' => 'Previous draft.',
    ]);

    $this->actingAs($user)
        ->post(route('discovery.outreaches.regenerate', $outreach), [
            'message_guidance' => 'Focus on client progress and keep it concise.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Outreach draft rebuild queued.');

    $this->assertDatabaseHas('prospect_outreaches', [
        'id' => $outreach->id,
        'status' => 'superseded',
    ]);
    $rebuiltOutreach = ProspectOutreach::query()
        ->where('prospect_id', $prospect->id)
        ->where('status', ProspectOutreachStatus::Queued)
        ->sole();

    expect($rebuiltOutreach->prompt)->toContain('Focus on client progress and keep it concise.');
    Queue::assertPushed(GenerateProspectOutreach::class);
});

it('does not rebuild a draft with message guidance that is too long', function () {
    Queue::fake([GenerateProspectOutreach::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = readyForOutreach();
    $outreach = ProspectOutreach::factory()->for($prospect)->create([
        'status' => ProspectOutreachStatus::Draft,
    ]);

    $this->actingAs($user)
        ->from(route('discovery.prospects.show', $prospect))
        ->post(route('discovery.outreaches.regenerate', $outreach), [
            'message_guidance' => str_repeat('a', 1001),
        ])
        ->assertRedirect(route('discovery.prospects.show', $prospect))
        ->assertSessionHasErrors('message_guidance');

    expect($outreach->fresh()->status)->toBe(ProspectOutreachStatus::Draft);
    Queue::assertNothingPushed();
});

it('forbids outreach generation for a user without growth access', function () {
    Queue::fake([GenerateProspectOutreach::class]);

    $user = User::factory()->create();
    $prospect = readyForOutreach();

    $this->actingAs($user)
        ->post(route('discovery.prospects.outreach.generate', $prospect))
        ->assertForbidden();

    $this->assertDatabaseCount('prospect_outreaches', 0);
    Queue::assertNothingPushed();
});

it('prepares, approves, and marks an outreach draft as sent', function () {
    $prospect = readyForOutreach();
    $outreach = ProspectOutreach::factory()->for($prospect)->create();

    ProspectOutreachAgent::fake([outreachResponse()]);

    app()->call([new GenerateProspectOutreach($outreach->id), 'handle']);

    $this->assertDatabaseHas('prospect_outreaches', [
        'id' => $outreach->id,
        'status' => 'draft',
        'channel' => 'email',
        'public_email' => 'hello@strength.example.com',
    ]);
    ProspectOutreachAgent::assertPromptedTimes();

    $user = User::factory()->growthAdmin()->create();

    $this->actingAs($user)
        ->post(route('discovery.outreaches.approve', $outreach))
        ->assertRedirect();

    $this->assertDatabaseHas('prospect_outreaches', [
        'id' => $outreach->id,
        'status' => 'approved',
        'approved_by_user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('discovery.outreaches.mark-sent', $outreach))
        ->assertRedirect();

    $this->assertDatabaseHas('prospect_outreaches', [
        'id' => $outreach->id,
        'status' => 'sent',
    ]);
    $this->assertDatabaseHas('lead_activities', [
        'prospect_id' => $prospect->id,
        'type' => 'contacted',
    ]);
});

function readyForOutreach(): Prospect
{
    $prospect = Prospect::factory()->create([
        'name' => 'Strength Studio Barcelona',
        'status' => ProspectStatus::Qualified,
        'score' => 82,
        'suggested_angle' => 'Centralize client plans and follow-up.',
        'signals' => ['Offers personal training.'],
    ]);

    ProspectEnrichment::factory()->for($prospect)->create([
        'status' => ProspectEnrichmentStatus::Completed,
    ]);

    return $prospect;
}

/** @return array<string, mixed> */
function outreachResponse(): array
{
    return [
        'channel' => 'email',
        'channel_url' => null,
        'public_email' => 'hello@strength.example.com',
        'recipient_name' => null,
        'recipient_role' => null,
        'language' => 'es',
        'subject' => 'Seguimiento de clientes en Strength Studio',
        'message' => 'Hola, he visto que ofrecéis entrenamiento personal en Barcelona. WildForce puede centralizar planes y seguimiento de clientes. ¿Te va bien verlo en una demo breve?',
        'rationale' => 'The website publishes a professional email and offers personal training.',
        'selected_value_proposition_keys' => ['scheduled_check_ins'],
        'sources' => [[
            'name' => 'Strength Studio website',
            'url' => 'https://strength.example.com',
            'excerpt' => 'Entrenamiento personal en Barcelona.',
        ]],
    ];
}
