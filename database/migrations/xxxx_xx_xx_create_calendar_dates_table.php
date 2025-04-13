<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('calendar_dates', function (Blueprint $table) {
            $table->id();
            $table->string('service_id');
            $table->date('date');
            $table->tinyInteger('exception_type');
            $table->timestamps();

            $table->index('service_id');
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_dates');
    }
}; 