<?php

namespace Database\Seeders;

use App\Models\ProspectEnrichment;
use Illuminate\Database\Seeder;

class ProspectEnrichmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProspectEnrichment::factory()->count(10)->create();
    }
}
