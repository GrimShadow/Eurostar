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
        Schema::create('realtime_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->string('stop_id');
            $table->string('field_type'); // 'departure_time', 'status', 'platform'
            $table->text('manual_value'); // The value the user manually set
            $table->text('realtime_value'); // The value from GTFS realtime
            $table->foreignId('manual_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('resolution')->nullable(); // 'use_realtime', 'keep_manual'
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Indexes for faster lookups
            $table->index(['trip_id', 'stop_id']);
            $table->index('field_type');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realtime_conflicts');
    }
};
