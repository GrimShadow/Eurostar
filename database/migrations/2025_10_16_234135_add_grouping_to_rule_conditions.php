<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->string('group_id')->nullable()->after('train_rule_id');
            $table->enum('group_operator', ['and', 'or'])->nullable()->after('group_id');
            $table->integer('nesting_level')->default(0)->after('group_operator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->dropColumn(['group_id', 'group_operator', 'nesting_level']);
        });
    }
};
