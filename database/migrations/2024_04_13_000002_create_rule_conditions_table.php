<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('train_rules')->onDelete('cascade');
            $table->string('condition_type');
            $table->string('operator');
            $table->string('value');
            $table->string('logical_operator')->nullable(); // 'and' or 'or'
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rule_conditions');
    }
}; 