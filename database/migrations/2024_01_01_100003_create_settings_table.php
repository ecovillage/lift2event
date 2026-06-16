<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('map_center_lat', 10, 7)->default(50.9333);
            $table->decimal('map_center_lng', 10, 7)->default(10.5511);
            $table->unsignedTinyInteger('map_zoom')->default(6);
            $table->json('footer_links')->nullable();
            $table->timestamps();
        });

        // Insert the single settings row with defaults (center of Europe)
        DB::table('settings')->insert([
            'map_center_lat' => 50.9333,
            'map_center_lng' => 10.5511,
            'map_zoom' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
