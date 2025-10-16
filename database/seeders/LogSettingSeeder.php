<?php

namespace Database\Seeders;

use App\Models\LogSetting;
use Illuminate\Database\Seeder;

class LogSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LogSetting::firstOrCreate(
            ['id' => 1], // Ensure we only have one record
            [
                'gtfs_error_logs' => false,
                'gtfs_debug_logs' => false,
                'gtfs_information_logs' => false,
                'aviavox_error_logs' => false,
                'aviavox_debug_logs' => false,
                'aviavox_information_logs' => false,
                'automatic_rules_error_logs' => false,
                'automatic_rules_debug_logs' => false,
                'automatic_rules_information_logs' => false,
                'announcement_error_logs' => false,
                'announcement_debug_logs' => false,
                'announcement_information_logs' => false,
            ]
        );
    }
}
