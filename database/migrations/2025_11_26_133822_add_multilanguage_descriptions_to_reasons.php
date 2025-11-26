<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing data to JSON format
        $reasons = DB::table('reasons')->get();

        foreach ($reasons as $reason) {
            $description = $reason->description;

            // Convert existing description to JSON format with English as default
            if ($description !== null && $description !== '') {
                $jsonDescription = json_encode(['en' => $description]);
            } else {
                $jsonDescription = json_encode(['en' => '']);
            }

            // Update the description as JSON string temporarily
            DB::table('reasons')
                ->where('id', $reason->id)
                ->update(['description' => $jsonDescription]);
        }

        // Change column type from text to json
        Schema::table('reasons', function (Blueprint $table) {
            $table->json('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON back to text (extract English description)
        $reasons = DB::table('reasons')->get();

        foreach ($reasons as $reason) {
            $description = $reason->description;

            // Extract English description from JSON
            if ($description !== null) {
                $decoded = json_decode($description, true);
                $englishDescription = is_array($decoded) && isset($decoded['en'])
                    ? $decoded['en']
                    : (is_string($description) ? $description : '');
            } else {
                $englishDescription = '';
            }

            // Update back to text
            DB::table('reasons')
                ->where('id', $reason->id)
                ->update(['description' => $englishDescription]);
        }

        // Change column type back from json to text
        Schema::table('reasons', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};
