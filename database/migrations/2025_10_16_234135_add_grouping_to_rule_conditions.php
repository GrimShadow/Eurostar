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
            if (!Schema::hasColumn('rule_conditions', 'group_id')) {
                $table->string('group_id')->nullable()->after('train_rule_id');
            }
            if (!Schema::hasColumn('rule_conditions', 'group_operator')) {
                $table->enum('group_operator', ['and', 'or'])->nullable()->after('group_id');
            }
            if (!Schema::hasColumn('rule_conditions', 'nesting_level')) {
                $table->integer('nesting_level')->default(0)->after('group_operator');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('rule_conditions', 'group_id')) {
                $columnsToDrop[] = 'group_id';
            }
            if (Schema::hasColumn('rule_conditions', 'group_operator')) {
                $columnsToDrop[] = 'group_operator';
            }
            if (Schema::hasColumn('rule_conditions', 'nesting_level')) {
                $columnsToDrop[] = 'nesting_level';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
