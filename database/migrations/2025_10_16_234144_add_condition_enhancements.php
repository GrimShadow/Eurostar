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
            if (!Schema::hasColumn('rule_conditions', 'value_secondary')) {
                $table->string('value_secondary')->nullable()->after('value');
            }
            if (!Schema::hasColumn('rule_conditions', 'threshold_type')) {
                $table->enum('threshold_type', ['absolute', 'percentage'])->default('absolute')->after('value_secondary');
            }
            if (!Schema::hasColumn('rule_conditions', 'reference_field')) {
                $table->string('reference_field')->nullable()->after('threshold_type');
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
            if (Schema::hasColumn('rule_conditions', 'value_secondary')) {
                $columnsToDrop[] = 'value_secondary';
            }
            if (Schema::hasColumn('rule_conditions', 'threshold_type')) {
                $columnsToDrop[] = 'threshold_type';
            }
            if (Schema::hasColumn('rule_conditions', 'reference_field')) {
                $columnsToDrop[] = 'reference_field';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
