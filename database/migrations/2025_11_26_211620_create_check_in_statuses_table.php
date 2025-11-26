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
        Schema::create('check_in_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status')->unique();
            $table->string('color_name')->nullable();
            $table->string('color_rgb')->nullable(); // Store RGB value as string (e.g., "255,0,0")
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_statuses');
    }
};
