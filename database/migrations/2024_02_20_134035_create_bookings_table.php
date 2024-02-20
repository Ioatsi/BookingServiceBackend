<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('booking_groups');
            $table->foreignId('booker_id')->constrained('users');
            $table->foreignId('semester_id');
            $table->foreignId('room_id')->constrained('rooms');
            $table->string('status');
            $table->string('title');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('color');
            $table->string('info');
            $table->string('participants');
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
