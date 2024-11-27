<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtfs_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp');
            $table->string('status');
            $table->text('status_reason')->nullable();
            $table->timestamp('last_update_sent_timestamp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtfs_heartbeats');
    }
};