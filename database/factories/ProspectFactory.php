<?php

namespace Database\Factories;

use App\Enums\ProspectStatus;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->unique()->domainName();

        return [
            'canonical_key' => 'domain:'.$domain,
            'name' => fake()->company(),
            'segment' => fake()->randomElement(['coach', 'boutique_gym', 'gym']),
            'website' => 'https://'.$domain,
            'domain' => $domain,
            'city' => 'Barcelona',
            'province' => 'Barcelona',
            'country' => 'ES',
            'social_profiles' => [],
            'status' => ProspectStatus::Discovered,
            'detected_services' => [],
            'signals' => [],
            'last_discovered_at' => now(),
        ];
    }
}
