<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stop_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('stop_statuses', 'status_color')) {
                $table->string('status_color')->nullable()->after('status');
            }
            if (!Schema::hasColumn('stop_statuses', 'status_color_hex')) {
                $table->string('status_color_hex')->nullable()->after('status_color');
            }
            if (!Schema::hasColumn('stop_statuses', 'departure_platform')) {
                $table->string('departure_platform')->nullable()->after('platform_code');
            }
            if (!Schema::hasColumn('stop_statuses', 'arrival_platform')) {
                $table->string('arrival_platform')->nullable()->after('departure_platform');
            }
        });
    }

    public function down()
    {
        Schema::table('stop_statuses', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('stop_statuses', 'status_color')) {
                $columnsToDrop[] = 'status_color';
            }
            if (Schema::hasColumn('stop_statuses', 'status_color_hex')) {
                $columnsToDrop[] = 'status_color_hex';
            }
            if (Schema::hasColumn('stop_statuses', 'departure_platform')) {
                $columnsToDrop[] = 'departure_platform';
            }
            if (Schema::hasColumn('stop_statuses', 'arrival_platform')) {
                $columnsToDrop[] = 'arrival_platform';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
}; 