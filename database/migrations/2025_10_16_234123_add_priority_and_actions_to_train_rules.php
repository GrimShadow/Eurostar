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
        Schema::table('train_rules', function (Blueprint $table) {
            $table->integer('priority')->default(0)->after('is_active');
            $table->json('action')->change();
            $table->json('action_value')->change();
            $table->enum('execution_mode', ['first_match', 'all_matches', 'highest_priority'])->default('first_match')->after('priority');

            // Add indexes for performance
            $table->index(['is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('train_rules', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'priority']);
            $table->dropColumn(['priority', 'execution_mode']);
            $table->string('action')->change();
            $table->string('action_value')->change();
        });
    }
};
