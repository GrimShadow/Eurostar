<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                // First, clean up any invalid JSON data
                $this->cleanupActionData();
                $table->json('action')->change();
            }
            if (Schema::hasColumn('train_rules', 'action_value')) {
                // First, clean up any invalid JSON data
                $this->cleanupActionValueData();
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

    /**
     * Clean up action data to ensure it's valid JSON
     */
    private function cleanupActionData(): void
    {
        $rules = DB::table('train_rules')->whereNotNull('action')->get();
        
        foreach ($rules as $rule) {
            $action = $rule->action;
            
            // If it's already valid JSON, skip
            if (is_string($action) && json_decode($action) !== null) {
                continue;
            }
            
            // If it's a string but not JSON, wrap it in an array
            if (is_string($action) && !empty($action)) {
                $jsonAction = json_encode([$action]);
            } else {
                // If it's null or empty, set a default
                $jsonAction = json_encode(['set_status']);
            }
            
            DB::table('train_rules')
                ->where('id', $rule->id)
                ->update(['action' => $jsonAction]);
        }
    }

    /**
     * Clean up action_value data to ensure it's valid JSON
     */
    private function cleanupActionValueData(): void
    {
        $rules = DB::table('train_rules')->whereNotNull('action_value')->get();
        
        foreach ($rules as $rule) {
            $actionValue = $rule->action_value;
            
            // If it's already valid JSON, skip
            if (is_string($actionValue) && json_decode($actionValue) !== null) {
                continue;
            }
            
            // If it's a string but not JSON, wrap it in an array
            if (is_string($actionValue) && !empty($actionValue)) {
                $jsonActionValue = json_encode([$actionValue]);
            } else {
                // If it's null or empty, set a default
                $jsonActionValue = json_encode([null]);
            }
            
            DB::table('train_rules')
                ->where('id', $rule->id)
                ->update(['action_value' => $jsonActionValue]);
        }
    }

};
