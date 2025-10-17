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
            if (!Schema::hasColumn('stop_statuses', 'is_realtime_update')) {
                $table->boolean('is_realtime_update')->default(false)->after('arrival_platform');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stop_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('stop_statuses', 'is_realtime_update')) {
                $table->dropColumn('is_realtime_update');
            }
        });
    }
};
