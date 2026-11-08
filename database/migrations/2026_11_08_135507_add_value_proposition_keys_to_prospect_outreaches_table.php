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
        Schema::table('prospect_outreaches', function (Blueprint $table) {
            $table->json('value_proposition_keys')->nullable()->after('rationale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_outreaches', function (Blueprint $table) {
            $table->dropColumn('value_proposition_keys');
        });
    }
};
