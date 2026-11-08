<?php

namespace Database\Factories;

use App\Enums\DiscoveryCampaignStatus;
use App\Models\DiscoveryCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryCampaign>
 */
class DiscoveryCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'segments' => ['coach'],
            'locations' => ['Barcelona, Spain'],
            'niches' => ['strength'],
            'language' => 'ca',
            'daily_candidate_limit' => 10,
            'status' => DiscoveryCampaignStatus::Active,
        ];
    }
}
