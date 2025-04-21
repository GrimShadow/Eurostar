<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('train_rules', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['condition_type', 'operator', 'value']);
            
            // Make action_value nullable since it might not be set immediately
            $table->string('action_value')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('train_rules', function (Blueprint $table) {
            // Add back the old columns
            $table->string('condition_type');
            $table->string('operator');
            $table->string('value');
            
            // Make action_value non-nullable again
            $table->string('action_value')->change();
        });
    }
}; 