<?php

use App\Enums\DiscoveryRunStatus;
use App\Models\DiscoveryCampaign;
use App\Models\User;
use App\Services\Discovery\ProspectDiscoveryService;

it('deduplicates a prospect by its normalized website domain', function () {
    $campaign = DiscoveryCampaign::factory()->for(User::factory(), 'creator')->create();
    $firstRun = $campaign->runs()->create([
        'status' => DiscoveryRunStatus::Queued,
        'provider' => 'openai',
        'model' => 'gpt-4.1-mini',
        'prompt' => 'First run',
        'candidate_limit' => 10,
    ]);
    $secondRun = $campaign->runs()->create([
        'status' => DiscoveryRunStatus::Queued,
        'provider' => 'openai',
        'model' => 'gpt-4.1-mini',
        'prompt' => 'Second run',
        'candidate_limit' => 10,
    ]);
    $candidate = [
        'business_name' => 'Strength Studio Barcelona',
        'segment' => 'boutique_gym',
        'website' => 'https://www.strength.example.com',
        'city' => 'Barcelona',
        'province' => 'Barcelona',
        'country' => 'ES',
        'public_phone' => null,
        'social_profiles' => [],
        'detected_services' => ['personal training'],
        'signals' => ['Offers personal training.'],
        'score' => 82,
        'confidence' => 'high',
        'rationale' => 'It offers personal training and has a local studio.',
        'suggested_angle' => 'Bring plans and client progress into one workspace.',
        'sources' => [[
            'name' => 'Strength Studio website',
            'url' => 'https://www.strength.example.com',
            'excerpt' => 'Personal training in Barcelona.',
        ]],
    ];

    $service = app(ProspectDiscoveryService::class);
    $service->storeCandidates($firstRun, [$candidate]);
    $service->storeCandidates($secondRun, [$candidate]);

    $this->assertDatabaseCount('prospects', 1);
    $this->assertDatabaseCount('prospect_observations', 1);
    $this->assertDatabaseCount('prospect_assessments', 2);
});
