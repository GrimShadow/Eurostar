<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_route_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('route_id');
            $table->string('stop_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['group_id', 'route_id', 'stop_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_route_stations');
    }
}; 