<?php

namespace Database\Factories;

use App\Enums\ProspectOutreachStatus;
use App\Models\Prospect;
use App\Models\ProspectOutreach;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProspectOutreach>
 */
class ProspectOutreachFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prospect_id' => Prospect::factory(),
            'status' => ProspectOutreachStatus::Queued,
            'provider' => 'openai',
            'model' => 'gpt-6-luna',
            'prompt' => fake()->paragraph(),
        ];
    }
}
