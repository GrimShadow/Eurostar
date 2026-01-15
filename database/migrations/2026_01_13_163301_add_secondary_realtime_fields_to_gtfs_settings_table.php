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
        Schema::table('gtfs_settings', function (Blueprint $table) {
            $table->string('realtime_source')->default('primary')->after('realtime_status');
            $table->string('secondary_realtime_url')->nullable()->after('realtime_source');
            $table->integer('secondary_realtime_update_interval')->default(30)->after('secondary_realtime_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_settings', function (Blueprint $table) {
            $table->dropColumn([
                'realtime_source',
                'secondary_realtime_url',
                'secondary_realtime_update_interval',
            ]);
        });
    }
};
