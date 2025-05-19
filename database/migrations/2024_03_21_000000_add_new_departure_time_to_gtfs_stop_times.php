<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->time('new_departure_time')->nullable()->after('departure_time');
        });
    }

    public function down()
    {
        Schema::table('gtfs_stop_times', function (Blueprint $table) {
            $table->dropColumn('new_departure_time');
        });
    }
}; 