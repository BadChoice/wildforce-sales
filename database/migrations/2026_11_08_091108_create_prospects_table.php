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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_key')->unique();
            $table->string('name');
            $table->string('segment');
            $table->string('website')->nullable();
            $table->string('domain')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country', 2)->default('ES');
            $table->string('public_phone')->nullable();
            $table->json('social_profiles')->nullable();
            $table->string('status')->default('discovered');
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('confidence')->nullable();
            $table->text('suggested_angle')->nullable();
            $table->json('detected_services')->nullable();
            $table->json('signals')->nullable();
            $table->timestamp('last_discovered_at')->nullable();
            $table->timestamps();

            $table->index(['segment', 'city']);
            $table->index(['status', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
