<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->renameColumn('rule_id', 'train_rule_id');
        });
    }

    public function down()
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->renameColumn('train_rule_id', 'rule_id');
        });
    }
}; 