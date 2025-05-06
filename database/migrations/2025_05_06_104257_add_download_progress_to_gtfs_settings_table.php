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
            if (!Schema::hasColumn('gtfs_settings', 'download_progress')) {
                $table->integer('download_progress')->nullable();
            }
            if (!Schema::hasColumn('gtfs_settings', 'download_status')) {
                $table->string('download_status')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gtfs_settings', function (Blueprint $table) {
            $table->dropColumn(['download_progress', 'download_status']);
        });
    }
};
