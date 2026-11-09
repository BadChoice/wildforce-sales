<?php

use App\Ai\Agents\ProspectDiscoveryAgent;
use App\Enums\DiscoveryRunStatus;
use App\Jobs\RunDiscoveryCampaign;
use App\Mail\DiscoveryCompleted;
use App\Models\DiscoveryCampaign;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('stores sourced candidates returned by the discovery agent', function () {
    Mail::fake();

    $campaign = DiscoveryCampaign::factory()->for(User::factory(), 'creator')->create();
    $run = $campaign->runs()->create([
        'status' => DiscoveryRunStatus::Queued,
        'provider' => 'openai',
        'model' => 'gpt-4.1-mini',
        'prompt' => 'Discover personal trainers in Barcelona.',
        'candidate_limit' => 10,
    ]);

    ProspectDiscoveryAgent::fake([[
        'candidates' => [[
            'business_name' => 'Strength Studio Barcelona',
            'segment' => 'boutique_gym',
            'website' => 'https://strength.example.com',
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'country' => 'España',
            'public_phone' => null,
            'social_profiles' => [],
            'detected_services' => ['personal training'],
            'signals' => ['Offers personal training.'],
            'score' => 82,
            'confidence' => 'high',
            'rationale' => 'It offers personal training and has a local studio.',
            'sources' => [[
                'name' => 'Strength Studio website',
                'url' => 'https://strength.example.com',
                'excerpt' => 'Personal training in Barcelona.',
            ]],
        ]],
    ]]);

    app()->call([new RunDiscoveryCampaign($run->id), 'handle']);

    $this->assertDatabaseHas('prospects', [
        'name' => 'Strength Studio Barcelona',
        'status' => 'discovered',
        'score' => 82,
        'country' => 'ES',
    ]);
    $this->assertDatabaseHas('prospect_observations', [
        'source_name' => 'Strength Studio website',
    ]);
    $this->assertDatabaseHas('lead_activities', ['type' => 'discovered']);

    expect($run->fresh()->status)->toBe(DiscoveryRunStatus::Completed);
    ProspectDiscoveryAgent::assertPromptedTimes();
    Mail::assertQueued(DiscoveryCompleted::class, function (DiscoveryCompleted $mail) use ($run): bool {
        return $mail->hasTo('hello@wildforce.app') && $mail->run->is($run);
    });
});
