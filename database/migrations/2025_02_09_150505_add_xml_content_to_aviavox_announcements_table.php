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
        Schema::table('aviavox_announcements', function (Blueprint $table) {
            $table->text('xml_content')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aviavox_announcements', function (Blueprint $table) {
            $table->dropColumn('xml_content');
        });
    }
};
