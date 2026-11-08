<?php

use App\Enums\DiscoveryRunStatus;
use App\Jobs\RunDiscoveryCampaign;
use App\Models\DiscoveryCampaign;
use App\Models\DiscoveryRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('redirects guests away from discovery campaigns', function () {
    $this->get(route('discovery.campaigns.index'))
        ->assertRedirect(route('login'));
});

it('forbids users without growth access from discovery campaigns', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('discovery.campaigns.index'))
        ->assertForbidden();
});

it('renders discovery campaigns for a growth user', function () {
    $user = User::factory()->growthAdmin()->create();

    $this->actingAs($user)
        ->get(route('discovery.campaigns.index'))
        ->assertOk()
        ->assertSee('Discovery campaigns');
});

it('creates an active discovery campaign for a growth user', function () {
    $user = User::factory()->growthAdmin()->create();

    $this->actingAs($user)
        ->post(route('discovery.campaigns.store'), [
            'name' => 'Barcelona personal trainers',
            'segments' => 'coach',
            'locations' => 'Barcelona, Spain',
            'niches' => 'strength, functional fitness',
            'language' => 'ca',
            'daily_candidate_limit' => 10,
        ])
        ->assertRedirect(route('discovery.campaigns.index'));

    $this->assertDatabaseHas('discovery_campaigns', [
        'created_by_user_id' => $user->id,
        'name' => 'Barcelona personal trainers',
        'status' => 'active',
        'daily_candidate_limit' => 10,
    ]);
});

it('queues a discovery run for a growth user', function () {
    Queue::fake([RunDiscoveryCampaign::class]);

    $user = User::factory()->growthAdmin()->create();
    $campaign = DiscoveryCampaign::factory()->for($user, 'creator')->create();

    $this->actingAs($user)
        ->post(route('discovery.campaigns.run', $campaign))
        ->assertRedirect()
        ->assertSessionHas('status', "Discovery run queued for '{$campaign->name}'.");

    $this->assertDatabaseHas('discovery_runs', [
        'discovery_campaign_id' => $campaign->id,
        'status' => 'queued',
    ]);

    Queue::assertPushed(RunDiscoveryCampaign::class, fn (RunDiscoveryCampaign $job): bool => $job->discoveryRunId === 1);
});

it('refreshes the campaigns page while a discovery run is active', function () {
    $user = User::factory()->growthAdmin()->create();
    $campaign = DiscoveryCampaign::factory()->for($user, 'creator')->create();
    DiscoveryRun::factory()->for($campaign, 'campaign')->create([
        'status' => DiscoveryRunStatus::Running,
    ]);

    $this->actingAs($user)
        ->get(route('discovery.campaigns.index'))
        ->assertOk()
        ->assertSee('window.setTimeout(() => window.location.reload(), 5000);', false);
});
