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
        Schema::create('building_department', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('building_id');

            $table->foreign('department_id')->references('id')->on('Departments')->onDelete('cascade');
            $table->foreign('building_id')->references('id')->on('Buildings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_department');
    }
};
