<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // audio or text
            $table->text('message');
            $table->time('scheduled_time');
            $table->string('recurrence')->nullable(); // e.g., "2x 5 mins"
            $table->string('author');
            $table->string('area'); // e.g., "Terminal"
            $table->string('status'); // e.g., "Finished", "Pending", "Cancelled"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
