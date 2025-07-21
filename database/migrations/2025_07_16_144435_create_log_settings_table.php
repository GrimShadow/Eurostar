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
        Schema::create('log_settings', function (Blueprint $table) {
            $table->id();
            
            // GTFS Logging Flags
            $table->boolean('gtfs_error_logs')->default(false);
            $table->boolean('gtfs_debug_logs')->default(false);
            $table->boolean('gtfs_information_logs')->default(false);
            
            // Aviavox Logging Flags
            $table->boolean('aviavox_error_logs')->default(false);
            $table->boolean('aviavox_debug_logs')->default(false);
            $table->boolean('aviavox_information_logs')->default(false);
            
            // Automatic Rules Logging Flags
            $table->boolean('automatic_rules_error_logs')->default(false);
            $table->boolean('automatic_rules_debug_logs')->default(false);
            $table->boolean('automatic_rules_information_logs')->default(false);
            
            // Announcement Logging Flags
            $table->boolean('announcement_error_logs')->default(false);
            $table->boolean('announcement_debug_logs')->default(false);
            $table->boolean('announcement_information_logs')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_settings');
    }
};
