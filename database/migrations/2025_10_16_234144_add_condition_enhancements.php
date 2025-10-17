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
            $table->string('value_secondary')->nullable()->after('value');
            $table->enum('threshold_type', ['absolute', 'percentage'])->default('absolute')->after('value_secondary');
            $table->string('reference_field')->nullable()->after('threshold_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_conditions', function (Blueprint $table) {
            $table->dropColumn(['value_secondary', 'threshold_type', 'reference_field']);
        });
    }
};
