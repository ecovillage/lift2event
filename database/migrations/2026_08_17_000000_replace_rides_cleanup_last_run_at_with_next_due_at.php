<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('rides_cleanup_last_run_at');
            $table->timestamp('rides_cleanup_next_due_at')->nullable()->after('ride_data_retention_days');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('rides_cleanup_next_due_at');
            $table->timestamp('rides_cleanup_last_run_at')->nullable()->after('ride_data_retention_days');
        });
    }
};
