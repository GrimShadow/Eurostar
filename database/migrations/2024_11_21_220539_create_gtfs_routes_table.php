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
        Schema::create('gtfs_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_id')->unique();
            $table->string('agency_id');
            $table->string('route_short_name');
            $table->string('route_long_name');
            $table->unsignedTinyInteger('route_type');
            $table->string('route_color', 6)->nullable();
            $table->string('route_text_color', 6)->nullable();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtfs_routes');
    }
};
