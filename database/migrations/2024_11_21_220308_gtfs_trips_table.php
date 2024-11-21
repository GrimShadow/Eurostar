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
        Schema::create('gtfs_trips', function (Blueprint $table) {
            $table->id();
            $table->string('route_id');
            $table->string('service_id');
            $table->string('trip_id')->unique();
            $table->string('trip_headsign');
            $table->string('trip_short_name');
            $table->unsignedTinyInteger('direction_id');
            $table->string('shape_id');
            $table->boolean('wheelchair_accessible'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtfs_trips');
    }
};
