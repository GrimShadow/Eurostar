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
        // Add manual change tracking to gtfs_stop_times
        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->boolean('is_manual_change')->default(false)->after('new_departure_time');
            $table->foreignId('manually_changed_by')->nullable()->constrained('users')->onDelete('set null')->after('is_manual_change');
            $table->timestamp('manually_changed_at')->nullable()->after('manually_changed_by');
        });

        // Add manual change tracking to stop_statuses
        Schema::table('stop_statuses', function (Blueprint $table) {
            $table->boolean('is_manual_change')->default(false)->after('is_realtime_update');
            $table->foreignId('manually_changed_by')->nullable()->constrained('users')->onDelete('set null')->after('is_manual_change');
            $table->timestamp('manually_changed_at')->nullable()->after('manually_changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->dropForeign(['manually_changed_by']);
            $table->dropColumn(['is_manual_change', 'manually_changed_by', 'manually_changed_at']);
        });

        Schema::table('stop_statuses', function (Blueprint $table) {
            $table->dropForeign(['manually_changed_by']);
            $table->dropColumn(['is_manual_change', 'manually_changed_by', 'manually_changed_at']);
        });
    }
};
