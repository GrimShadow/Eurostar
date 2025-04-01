<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('train_rules', function (Blueprint $table) {
            $table->text('announcement_text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('train_rules', function (Blueprint $table) {
            $table->dropColumn('announcement_text');
        });
    }
}; 