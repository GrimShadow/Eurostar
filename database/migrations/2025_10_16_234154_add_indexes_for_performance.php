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
        Schema::table('stop_statuses', function (Blueprint $table) {
            if (! $this->indexExists('stop_statuses', 'stop_statuses_trip_id_stop_id_index')) {
                $table->index(['trip_id', 'stop_id']);
            }
            if (! $this->indexExists('stop_statuses', 'stop_statuses_is_realtime_update_index')) {
                $table->index('is_realtime_update');
            }
        });

        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            if (! $this->indexExists('gtfs_stop_times', 'gtfs_stop_times_trip_id_stop_sequence_index')) {
                $table->index(['trip_id', 'stop_sequence']);
            }
        });

        Schema::table('train_rule_executions', function (Blueprint $table) {
            if (! $this->indexExists('train_rule_executions', 'train_rule_executions_rule_id_trip_id_stop_id_index')) {
                $table->index(['rule_id', 'trip_id', 'stop_id']);
            }
            if (! $this->indexExists('train_rule_executions', 'train_rule_executions_executed_at_index')) {
                $table->index('executed_at');
            }
        });
    }

    private function indexExists($table, $index)
    {
        $indexes = \DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $indexInfo) {
            if ($indexInfo->Key_name === $index) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stop_statuses', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'stop_id']);
            $table->dropIndex(['is_realtime_update']);
        });

        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'stop_sequence']);
        });

        Schema::table('train_rule_executions', function (Blueprint $table) {
            $table->dropIndex(['rule_id', 'trip_id', 'stop_id']);
            $table->dropIndex(['executed_at']);
        });
    }
};
