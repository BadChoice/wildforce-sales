<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discovery_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->json('segments');
            $table->json('locations');
            $table->json('niches')->nullable();
            $table->string('language', 10)->default('ca');
            $table->unsignedSmallInteger('daily_candidate_limit')->default(10);
            $table->string('status')->default('draft');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discovery_campaigns');
    }
};
