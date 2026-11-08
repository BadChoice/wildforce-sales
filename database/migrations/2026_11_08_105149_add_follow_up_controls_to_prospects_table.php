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
        Schema::table('prospects', function (Blueprint $table) {
            $table->timestamp('contacted_at')->nullable()->after('last_discovered_at');
            $table->timestamp('replied_at')->nullable()->after('contacted_at');
            $table->timestamp('follow_up_paused_at')->nullable()->after('replied_at');
            $table->string('follow_up_pause_reason')->nullable()->after('follow_up_paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['contacted_at', 'replied_at', 'follow_up_paused_at', 'follow_up_pause_reason']);
        });
    }
};
