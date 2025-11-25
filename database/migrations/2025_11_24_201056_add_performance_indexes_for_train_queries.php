<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add composite index for gtfs_stop_times queries (trip_id + stop_id + departure_time)
        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            if (! $this->indexExists('gtfs_stop_times', 'idx_stop_times_trip_stop_departure')) {
                $table->index(['trip_id', 'stop_id', 'departure_time'], 'idx_stop_times_trip_stop_departure');
            }
        });

        // Add composite index for stop_statuses queries
        Schema::table('stop_statuses', function (Blueprint $table) {
            if (! $this->indexExists('stop_statuses', 'idx_stop_statuses_trip_stop_updated')) {
                $table->index(['trip_id', 'stop_id', 'updated_at'], 'idx_stop_statuses_trip_stop_updated');
            }
        });

        // Add index for gtfs_stops platform_code lookups
        Schema::table('gtfs_stops', function (Blueprint $table) {
            if (! $this->indexExists('gtfs_stops', 'idx_stops_platform_code')) {
                $table->index(['stop_id', 'platform_code'], 'idx_stops_platform_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            if ($this->indexExists('gtfs_stop_times', 'idx_stop_times_trip_stop_departure')) {
                $table->dropIndex('idx_stop_times_trip_stop_departure');
            }
        });

        Schema::table('stop_statuses', function (Blueprint $table) {
            if ($this->indexExists('stop_statuses', 'idx_stop_statuses_trip_stop_updated')) {
                $table->dropIndex('idx_stop_statuses_trip_stop_updated');
            }
        });

        Schema::table('gtfs_stops', function (Blueprint $table) {
            if ($this->indexExists('gtfs_stops', 'idx_stops_platform_code')) {
                $table->dropIndex('idx_stops_platform_code');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $indexInfo) {
                if ($indexInfo->Key_name === $index) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist, return false
            return false;
        }

        return false;
    }
};
