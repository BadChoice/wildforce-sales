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
        Schema::create('prospect_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prospect_outreach_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');
            $table->text('body');
            $table->timestamp('received_at');
            $table->string('status')->default('queued');
            $table->string('provider');
            $table->string('model');
            $table->text('prompt');
            $table->string('intent')->nullable();
            $table->string('confidence')->nullable();
            $table->text('summary')->nullable();
            $table->string('recommended_action')->nullable();
            $table->text('suggested_reply')->nullable();
            $table->boolean('should_pause_follow_ups')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['prospect_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_replies');
    }
};
