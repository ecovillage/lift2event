<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('edit_token');
        });

        // Rides created before this column existed were already publicly
        // visible, so treat them as confirmed instead of hiding them.
        DB::table('rides')->update(['confirmed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
