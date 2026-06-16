<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['offer', 'request']);
            $table->enum('direction', ['both-ways', 'outbound-only', 'return-only']);
            $table->dateTime('outbound_at')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->unsignedSmallInteger('seats')->default(1);
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->json('contact_methods');
            $table->text('info')->nullable();
            $table->string('edit_token', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
