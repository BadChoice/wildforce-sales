<?php

namespace Database\Factories;

use App\Enums\ProspectEnrichmentStatus;
use App\Models\Prospect;
use App\Models\ProspectEnrichment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProspectEnrichment>
 */
class ProspectEnrichmentFactory extends Factory
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
            'status' => ProspectEnrichmentStatus::Queued,
            'provider' => 'openai',
            'model' => 'gpt-6-luna',
            'prompt' => fake()->paragraph(),
        ];
    }
}
