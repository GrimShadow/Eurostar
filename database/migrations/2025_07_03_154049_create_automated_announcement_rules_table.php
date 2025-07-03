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
        Schema::create('automated_announcement_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Rule name for identification
            $table->time('start_time'); // Start time (e.g., 08:00)
            $table->time('end_time'); // End time (e.g., 20:00)
            $table->integer('interval_minutes'); // Interval in minutes (e.g., 40)
            $table->string('days_of_week')->default('1,2,3,4,5,6,7'); // Days active (1=Mon, 7=Sun)
            $table->foreignId('aviavox_template_id')->constrained()->onDelete('cascade'); // Template to use
            $table->string('zone'); // Zone for announcement
            $table->json('template_variables')->nullable(); // Variables for the template
            $table->timestamp('last_triggered_at')->nullable(); // Last time rule was triggered
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automated_announcement_rules');
    }
};
