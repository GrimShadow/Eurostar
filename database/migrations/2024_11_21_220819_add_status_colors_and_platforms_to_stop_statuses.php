<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stop_statuses', function (Blueprint $table) {
            $table->string('status_color')->nullable()->after('status');
            $table->string('status_color_hex')->nullable()->after('status_color');
            $table->string('departure_platform')->nullable()->after('platform_code');
            $table->string('arrival_platform')->nullable()->after('departure_platform');
        });
    }

    public function down()
    {
        Schema::table('stop_statuses', function (Blueprint $table) {
            $table->dropColumn([
                'status_color',
                'status_color_hex',
                'departure_platform',
                'arrival_platform'
            ]);
        });
    }
}; 