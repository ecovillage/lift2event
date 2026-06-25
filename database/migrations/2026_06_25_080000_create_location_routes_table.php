<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('locations')->cascadeOnDelete();
            $table->json('geometry');
            $table->timestamps();

            $table->unique(['from_location_id', 'to_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_routes');
    }
};
