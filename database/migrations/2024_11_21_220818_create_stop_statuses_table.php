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
        Schema::create('stop_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->string('stop_id');
            $table->string('status')->default('on-time');
            $table->time('scheduled_arrival_time')->nullable();
            $table->time('scheduled_departure_time')->nullable();
            $table->time('actual_arrival_time')->nullable();
            $table->time('actual_departure_time')->nullable();
            $table->string('platform_code')->nullable();
            $table->timestamps();

            $table->foreign('trip_id')->references('trip_id')->on('gtfs_trips')->onDelete('cascade');
            $table->foreign('stop_id')->references('stop_id')->on('gtfs_stops')->onDelete('cascade');
            $table->unique(['trip_id', 'stop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_statuses');
    }
}; 