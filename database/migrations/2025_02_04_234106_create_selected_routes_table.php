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
        Schema::create('selected_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('route_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selected_routes');
    }
};
