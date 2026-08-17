<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Backs both the retention cutoff query in RideDataRetentionCleaner
            // and the MIN(end_at) lookup that reschedules next_due_at.
            $table->index('end_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['end_at']);
        });
    }
};
