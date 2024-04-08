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
        Schema::create('recurrings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('conflict_id')->nullable();
            $table->foreignId('booker_id')->constrained('users');
            $table->integer('publicity');
            $table->integer('status');
            $table->string('url')->nullable();
            $table->foreignId('semester_id')->constrained('semesters');
            $table->string('info');
            $table->string('participants');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reccuring');
    }
};
