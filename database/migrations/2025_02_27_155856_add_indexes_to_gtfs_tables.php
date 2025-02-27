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
        Schema::table('gtfs_trips', function (Blueprint $table) {
            $table->index('route_id');
            $table->index('service_id');
            $table->index('trip_id');
        });

        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->index(['trip_id', 'stop_sequence']);
            $table->index('departure_time');
        });

        Schema::table('gtfs_calendar_dates', function (Blueprint $table) {
            $table->index(['service_id', 'date', 'exception_type']);
        });

        Schema::table('train_statuses', function (Blueprint $table) {
            $table->index('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_trips', function (Blueprint $table) {
            $table->dropIndex(['route_id']);
            $table->dropIndex(['service_id']);
            $table->dropIndex(['trip_id']);
        });

        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'stop_sequence']);
            $table->dropIndex(['departure_time']);
        });

        Schema::table('gtfs_calendar_dates', function (Blueprint $table) {
            $table->dropIndex(['service_id', 'date', 'exception_type']);
        });

        Schema::table('train_statuses', function (Blueprint $table) {
            $table->dropIndex(['trip_id']);
        });
    }
};
