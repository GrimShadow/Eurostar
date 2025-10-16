<?php

namespace App\Services;

use App\Models\LogSetting;
use Illuminate\Support\Facades\Log;

class LogHelper
{
    private static $settings = null;

    /**
     * Get the log settings from database (cached for performance)
     */
    private static function getSettings()
    {
        if (self::$settings === null) {
            self::$settings = LogSetting::first();
        }

        return self::$settings;
    }

    /**
     * GTFS Logging Methods
     */
    public static function gtfsError(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->gtfs_error_logs) {
            Log::error("[GTFS] {$message}", $context);
        }
    }

    public static function gtfsDebug(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->gtfs_debug_logs) {
            Log::debug("[GTFS] {$message}", $context);
        }
    }

    public static function gtfsInfo(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->gtfs_information_logs) {
            Log::info("[GTFS] {$message}", $context);
        }
    }

    /**
     * Aviavox Logging Methods
     */
    public static function aviavoxError(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->aviavox_error_logs) {
            Log::error("[AVIAVOX] {$message}", $context);
        }
    }

    public static function aviavoxDebug(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->aviavox_debug_logs) {
            Log::debug("[AVIAVOX] {$message}", $context);
        }
    }

    public static function aviavoxInfo(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->aviavox_information_logs) {
            Log::info("[AVIAVOX] {$message}", $context);
        }
    }

    /**
     * Automatic Rules Logging Methods
     */
    public static function rulesError(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->automatic_rules_error_logs) {
            Log::error("[RULES] {$message}", $context);
        }
    }

    public static function rulesDebug(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->automatic_rules_debug_logs) {
            Log::debug("[RULES] {$message}", $context);
        }
    }

    public static function rulesInfo(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->automatic_rules_information_logs) {
            Log::info("[RULES] {$message}", $context);
        }
    }

    /**
     * Announcement Logging Methods
     */
    public static function announcementError(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->announcement_error_logs) {
            Log::error("[ANNOUNCEMENT] {$message}", $context);
        }
    }

    public static function announcementDebug(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->announcement_debug_logs) {
            Log::debug("[ANNOUNCEMENT] {$message}", $context);
        }
    }

    public static function announcementInfo(string $message, array $context = [])
    {
        $settings = self::getSettings();
        if ($settings && $settings->announcement_information_logs) {
            Log::info("[ANNOUNCEMENT] {$message}", $context);
        }
    }

    /**
     * Clear the cached settings (useful for testing or when settings change)
     */
    public static function clearCache()
    {
        self::$settings = null;
    }
}
