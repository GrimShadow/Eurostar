<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'background_image')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('background_image')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'background_image')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('background_image');
            });
        }
    }
}; 