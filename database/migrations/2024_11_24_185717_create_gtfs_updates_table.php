<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gtfs_updates', function (Blueprint $table) {
            $table->id();
            $table->string('gtfs_realtime_version');
            $table->integer('incrementality');
            $table->timestamp('timestamp');
            $table->json('entity_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtfs_updates');
    }
};