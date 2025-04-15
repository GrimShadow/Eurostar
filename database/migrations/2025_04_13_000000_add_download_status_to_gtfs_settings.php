<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('gtfs_settings', function (Blueprint $table) {
            $table->boolean('is_downloading')->default(false);
            $table->timestamp('download_started_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('gtfs_settings', function (Blueprint $table) {
            $table->dropColumn('is_downloading');
            $table->dropColumn('download_started_at');
        });
    }
}; 