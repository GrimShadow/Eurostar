<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('train_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('trip_id');
            $table->string('status')->default('on-time');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('train_statuses');
    }
};