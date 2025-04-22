<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('aviavox_responses', function (Blueprint $table) {
            $table->id();
            $table->string('announcement_id')->nullable();
            $table->string('status')->nullable();
            $table->string('message_name')->nullable();
            $table->text('raw_response');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('aviavox_responses');
    }
}; 