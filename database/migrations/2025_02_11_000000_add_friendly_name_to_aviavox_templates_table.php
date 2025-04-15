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
        Schema::table('aviavox_templates', function (Blueprint $table) {
            $table->string('friendly_name')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aviavox_templates', function (Blueprint $table) {
            $table->dropColumn('friendly_name');
        });
    }
}; 