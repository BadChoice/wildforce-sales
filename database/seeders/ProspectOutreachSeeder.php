<?php

namespace Database\Seeders;

use App\Models\ProspectOutreach;
use Illuminate\Database\Seeder;

class ProspectOutreachSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProspectOutreach::factory()->count(10)->create();
    }
}
