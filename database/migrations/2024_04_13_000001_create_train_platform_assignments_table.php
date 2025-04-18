<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('train_platform_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->string('stop_id');
            $table->string('platform_code');
            $table->timestamps();

            $table->unique(['trip_id', 'stop_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('train_platform_assignments');
    }
}; 