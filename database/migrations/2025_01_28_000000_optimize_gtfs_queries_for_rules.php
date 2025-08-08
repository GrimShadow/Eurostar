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
        // Add composite indexes for train rule queries
        Schema::table('gtfs_trips', function (Blueprint $table) {
            // Index for route_id + service_id (used in calendar date joins)
            $table->index(['route_id', 'service_id'], 'idx_trips_route_service');
            
            // Index for trip_id + route_id (used in stop time joins)
            $table->index(['trip_id', 'route_id'], 'idx_trips_id_route');
        });

        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            // Composite index for trip_id + stop_id + departure_time (most common query pattern)
            $table->index(['trip_id', 'stop_id', 'departure_time'], 'idx_stop_times_trip_stop_departure');
            
            // Index for trip_id + stop_sequence (used in first/last stop queries)
            $table->index(['trip_id', 'stop_sequence'], 'idx_stop_times_trip_sequence');
            
            // Index for departure_time (used in time range filters)
            $table->index('departure_time', 'idx_stop_times_departure');
        });

        Schema::table('gtfs_calendar_dates', function (Blueprint $table) {
            // Composite index for service_id + date + exception_type (used in calendar checks)
            $table->index(['service_id', 'date', 'exception_type'], 'idx_calendar_service_date_exception');
        });

        // Add indexes for group-related tables
        Schema::table('selected_routes', function (Blueprint $table) {
            $table->index(['route_id', 'is_active'], 'idx_selected_routes_active');
        });

        Schema::table('group_route_stations', function (Blueprint $table) {
            $table->index(['route_id', 'stop_id', 'is_active'], 'idx_group_route_stations_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_trips', function (Blueprint $table) {
            $table->dropIndex('idx_trips_route_service');
            $table->dropIndex('idx_trips_id_route');
        });

        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->dropIndex('idx_stop_times_trip_stop_departure');
            $table->dropIndex('idx_stop_times_trip_sequence');
            $table->dropIndex('idx_stop_times_departure');
        });

        Schema::table('gtfs_calendar_dates', function (Blueprint $table) {
            $table->dropIndex('idx_calendar_service_date_exception');
        });

        Schema::table('selected_routes', function (Blueprint $table) {
            $table->dropIndex('idx_selected_routes_active');
        });

        Schema::table('group_route_stations', function (Blueprint $table) {
            $table->dropIndex('idx_group_route_stations_active');
        });
    }
}; 