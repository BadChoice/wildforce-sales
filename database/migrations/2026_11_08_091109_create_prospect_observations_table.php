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
        Schema::create('prospect_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discovery_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_name');
            $table->text('source_url');
            $table->string('source_hash', 64);
            $table->text('excerpt')->nullable();
            $table->json('facts')->nullable();
            $table->timestamp('observed_at');
            $table->timestamps();

            $table->unique(['prospect_id', 'source_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_observations');
    }
};
