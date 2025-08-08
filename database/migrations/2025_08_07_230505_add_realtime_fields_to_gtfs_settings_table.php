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
            $table->string('realtime_url')->nullable()->after('url');
            $table->integer('realtime_update_interval')->default(30)->after('realtime_url');
            $table->timestamp('last_realtime_update')->nullable()->after('realtime_update_interval');
            $table->string('realtime_status')->nullable()->after('last_realtime_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_settings', function (Blueprint $table) {
            $table->dropColumn([
                'realtime_url',
                'realtime_update_interval',
                'last_realtime_update',
                'realtime_status'
            ]);
        });
    }
};
