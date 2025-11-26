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
        Schema::create('train_check_in_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->foreignId('check_in_status_id')->nullable()->constrained('check_in_statuses')->onDelete('set null');
            $table->timestamps();

            $table->foreign('trip_id')->references('trip_id')->on('gtfs_trips')->onDelete('cascade');
            $table->unique('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('train_check_in_statuses');
    }
};
