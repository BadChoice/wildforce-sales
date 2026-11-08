<?php

use App\Jobs\AnalyzeProspectReply;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('stores a manual reply and queues its analysis', function () {
    Queue::fake([AnalyzeProspectReply::class]);

    $user = User::factory()->growthAdmin()->create();
    $prospect = Prospect::factory()->create();

    $this->actingAs($user)
        ->post(route('discovery.prospects.replies.store', $prospect), [
            'channel' => 'email',
            'body' => 'Gracias, me interesa saber más.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', "Reply queued for analysis for '{$prospect->name}'.");

    $this->assertDatabaseHas('prospect_replies', [
        'prospect_id' => $prospect->id,
        'channel' => 'email',
        'status' => 'queued',
    ]);
    expect($prospect->fresh()->replied_at)->not->toBeNull();
    Queue::assertPushed(AnalyzeProspectReply::class);
});
