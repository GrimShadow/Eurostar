<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('train_rule_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->string('trip_id');
            $table->string('stop_id');
            $table->timestamp('executed_at');
            $table->string('action_taken'); // 'set_status', 'make_announcement', etc.
            $table->json('action_details')->nullable(); // Store details about what was done
            $table->timestamps();
            
            // Unique constraint to prevent duplicate executions
            $table->unique(['rule_id', 'trip_id', 'stop_id'], 'unique_rule_execution');
            
            // Indexes for performance
            $table->index(['rule_id', 'trip_id']);
            $table->index(['trip_id', 'stop_id']);
            $table->index('executed_at');
            
            // Foreign key constraint
            $table->foreign('rule_id')->references('id')->on('train_rules')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('train_rule_executions');
    }
}; 