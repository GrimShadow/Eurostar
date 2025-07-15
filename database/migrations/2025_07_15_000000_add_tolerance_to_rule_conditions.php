<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->integer('tolerance_minutes')->default(1)->after('value')
                ->comment('Tolerance window in minutes for equality operators to handle timing issues');
        });
    }

    public function down()
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->dropColumn('tolerance_minutes');
        });
    }
}; 