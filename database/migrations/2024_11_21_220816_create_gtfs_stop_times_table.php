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
        Schema::create('gtfs_stop_times', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->time('arrival_time');
            $table->time('departure_time');
            $table->string('stop_id');
            $table->unsignedInteger('stop_sequence');
            $table->unsignedTinyInteger('drop_off_type')->default(0);
            $table->unsignedTinyInteger('pickup_type')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtfs_stop_times');
    }
};
