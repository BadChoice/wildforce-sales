<?php

use App\Enums\DiscoveryRunStatus;
use App\Mail\DiscoveryCompleted;
use App\Models\DiscoveryCampaign;
use App\Models\User;

it('renders the completed run and its candidates', function () {
    $campaign = DiscoveryCampaign::factory()
        ->for(User::factory(), 'creator')
        ->create(['name' => 'Personal trainers in Barcelona']);
    $run = $campaign->runs()->create([
        'status' => DiscoveryRunStatus::Completed,
        'provider' => 'openai',
        'model' => 'gpt-6-luna',
        'prompt' => 'Discover personal trainers in Barcelona.',
        'candidate_limit' => 10,
        'discovered_count' => 1,
        'result' => ['candidates' => [[
            'business_name' => 'Strength Studio Barcelona',
            'segment' => 'boutique_gym',
            'city' => 'Barcelona',
            'sources' => [['url' => 'https://strength.example.com']],
        ]], ],
        'completed_at' => now(),
    ]);

    $mail = new DiscoveryCompleted($run);

    $mail->assertHasSubject('Discovery completed: Personal trainers in Barcelona');
    $mail->assertSeeInHtml('Strength Studio Barcelona');
    $mail->assertSeeInHtml('https://strength.example.com');
});
