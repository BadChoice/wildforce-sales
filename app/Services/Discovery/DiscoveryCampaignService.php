<?php

namespace App\Services\Discovery;

use App\Enums\DiscoveryCampaignStatus;
use App\Models\DiscoveryCampaign;
use App\Models\User;

class DiscoveryCampaignService
{
    /** @param array{name: string, segments: array<int, string>, locations: array<int, string>, niches: array<int, string>, language: string, daily_candidate_limit: int} $data */
    public function create(User $user, array $data): DiscoveryCampaign
    {
        return DiscoveryCampaign::create([
            ...$data,
            'created_by_user_id' => $user->id,
            'status' => DiscoveryCampaignStatus::Active,
        ]);
    }
}
