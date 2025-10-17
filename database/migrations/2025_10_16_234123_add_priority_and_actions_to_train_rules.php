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
            // Add priority column if it doesn't exist
            if (!Schema::hasColumn('train_rules', 'priority')) {
                $table->integer('priority')->default(0)->after('is_active');
            }
            
            // Add execution_mode column if it doesn't exist
            if (!Schema::hasColumn('train_rules', 'execution_mode')) {
                $table->enum('execution_mode', ['first_match', 'all_matches', 'highest_priority'])->default('first_match')->after('priority');
            }
            
            // Change action and action_value to JSON if they're not already
            if (Schema::hasColumn('train_rules', 'action')) {
                $table->json('action')->change();
            }
            if (Schema::hasColumn('train_rules', 'action_value')) {
                $table->json('action_value')->change();
            }

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
            // Drop index
            $table->dropIndex(['is_active', 'priority']);
            
            // Drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('train_rules', 'priority')) {
                $columnsToDrop[] = 'priority';
            }
            if (Schema::hasColumn('train_rules', 'execution_mode')) {
                $columnsToDrop[] = 'execution_mode';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Revert action and action_value to string if they exist
            if (Schema::hasColumn('train_rules', 'action')) {
                $table->string('action')->change();
            }
            if (Schema::hasColumn('train_rules', 'action_value')) {
                $table->string('action_value')->change();
            }
        });
    }

};
