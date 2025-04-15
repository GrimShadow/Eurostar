<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('train_rules', function (Blueprint $table) {
            $table->string('value')->change();
        });
    }

    public function down()
    {
        Schema::table('train_rules', function (Blueprint $table) {
            $table->integer('value')->change();
        });
    }
}; 