<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('aviavox_announcements', function (Blueprint $table) {
            $table->string('item_id')->nullable()->change();
            $table->string('value')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('aviavox_announcements', function (Blueprint $table) {
            $table->string('item_id')->nullable(false)->change();
            $table->string('value')->nullable(false)->change();
        });
    }
}; 