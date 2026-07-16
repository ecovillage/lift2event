<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('street')->nullable()->after('address');
            $table->string('house_number')->nullable()->after('street');
            $table->string('postal_code')->nullable()->after('house_number');
            $table->string('city')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['street', 'house_number', 'postal_code', 'city']);
        });
    }
};
