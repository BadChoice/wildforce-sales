<?php

namespace Database\Factories;

use App\Enums\DiscoveryRunStatus;
use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryRun>
 */
class DiscoveryRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => DiscoveryRunStatus::Queued,
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'prompt' => fake()->paragraph(),
            'candidate_limit' => fake()->numberBetween(1, 25),
        ];
    }
}
