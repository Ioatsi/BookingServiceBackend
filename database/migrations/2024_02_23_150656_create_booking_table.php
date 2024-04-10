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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('groups');
            $table->foreignId('recurring_id')->nullable()->constrained('recurrings');
            $table->string('conflict_id')->nullable();
            $table->foreignId('booker_id')->constrained('users');
            $table->foreignId('semester_id')->constrained('semesters');
            $table->foreignId('room_id')->constrained('rooms');
            $table->integer('status');
            $table->integer('publicity');
            $table->string('title');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('info')->nullable();
            $table->string('participants')->nullable();
            $table->string('type');
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
