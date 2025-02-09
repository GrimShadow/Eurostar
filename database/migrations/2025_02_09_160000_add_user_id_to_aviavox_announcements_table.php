<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('aviavox_announcements', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
            $table->enum('type', ['text', 'audio'])->default('audio');
            $table->string('description')->nullable();
        });
    }

    public function down()
    {
        Schema::table('aviavox_announcements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'type', 'description']);
        });
    }
}; 