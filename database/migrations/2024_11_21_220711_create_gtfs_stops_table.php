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
        Schema::create('gtfs_stops', function (Blueprint $table) {
            $table->id();
            $table->string('stop_id')->unique();
            $table->string('stop_code')->nullable();
            $table->string('stop_name');
            $table->decimal('stop_lon', 10, 6);
            $table->decimal('stop_lat', 9, 6);
            $table->string('stop_timezone')->nullable();
            $table->unsignedTinyInteger('location_type')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtfs_stops');
    }
};
