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
        if (Schema::hasTable('gtfs_trips')) {
            try {
                Schema::table('gtfs_trips', function (Blueprint $table) {
                    // Only add composite indexes that don't conflict with existing single-column indexes
                    // Index for trip_id + route_id (used in stop time joins)
                    $table->index(['trip_id', 'route_id'], 'idx_trips_id_route');
                });
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }

        if (Schema::hasTable('gtfs_stop_times')) {
            try {
                Schema::table('gtfs_stop_times', function (Blueprint $table) {
                    // Composite index for trip_id + stop_id + departure_time (most common query pattern)
                    $table->index(['trip_id', 'stop_id', 'departure_time'], 'idx_stop_times_trip_stop_departure');
                });
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }

        // Add indexes for group-related tables
        if (Schema::hasTable('selected_routes')) {
            try {
                Schema::table('selected_routes', function (Blueprint $table) {
                    $table->index(['route_id', 'is_active'], 'idx_selected_routes_active');
                });
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }

        if (Schema::hasTable('group_route_stations')) {
            try {
                Schema::table('group_route_stations', function (Blueprint $table) {
                    $table->index(['route_id', 'stop_id', 'is_active'], 'idx_group_route_stations_active');
                });
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('gtfs_trips')) {
            Schema::table('gtfs_trips', function (Blueprint $table) {
                $table->dropIndex('idx_trips_id_route');
            });
        }

        if (Schema::hasTable('gtfs_stop_times')) {
            Schema::table('gtfs_stop_times', function (Blueprint $table) {
                $table->dropIndex('idx_stop_times_trip_stop_departure');
            });
        }

        if (Schema::hasTable('selected_routes')) {
            Schema::table('selected_routes', function (Blueprint $table) {
                $table->dropIndex('idx_selected_routes_active');
            });
        }

        if (Schema::hasTable('group_route_stations')) {
            Schema::table('group_route_stations', function (Blueprint $table) {
                $table->dropIndex('idx_group_route_stations_active');
            });
        }
    }
}; 