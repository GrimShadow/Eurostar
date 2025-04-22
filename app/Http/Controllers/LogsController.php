<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LogsController extends Controller
{
    public function viewLogs()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');
            
            // Log the current user and file path for debugging
            Log::info('Attempting to view logs', [
                'user' => Auth::id() ?? 'unauthenticated',
                'file_path' => $logFilePath,
                'file_exists' => File::exists($logFilePath),
                'is_readable' => File::isReadable($logFilePath),
                'permissions' => File::exists($logFilePath) ? substr(sprintf('%o', fileperms($logFilePath)), -4) : 'N/A'
            ]);

            if (!File::exists($logFilePath)) {
                Log::warning('Log file not found at: ' . $logFilePath);
                return view('settings.logs', ['logContents' => 'No log file found.']);
            }

            if (!File::isReadable($logFilePath)) {
                Log::error('Log file is not readable: ' . $logFilePath, [
                    'permissions' => substr(sprintf('%o', fileperms($logFilePath)), -4),
                    'owner' => posix_getpwuid(fileowner($logFilePath))['name'] ?? 'unknown',
                    'group' => posix_getgrgid(filegroup($logFilePath))['name'] ?? 'unknown'
                ]);
                return view('settings.logs', ['logContents' => 'Log file is not readable. Please check file permissions.']);
            }

            // Try to read the file in chunks to handle large files
            $handle = fopen($logFilePath, 'r');
            if (!$handle) {
                Log::error('Failed to open log file: ' . $logFilePath);
                return view('settings.logs', ['logContents' => 'Failed to open log file.']);
            }

            $logContents = '';
            while (!feof($handle)) {
                $logContents .= fread($handle, 8192);
            }
            fclose($handle);

            return view('settings.logs', compact('logContents'));
        } catch (\Exception $e) {
            Log::error('Error accessing log file: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return view('settings.logs', ['logContents' => 'Error accessing log file: ' . $e->getMessage()]);
        }
    }

    public function exportLogs()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');

            Log::info('Attempting to export logs', [
                'user' => Auth::id() ?? 'unauthenticated',
                'file_path' => $logFilePath,
                'file_exists' => File::exists($logFilePath),
                'is_readable' => File::isReadable($logFilePath)
            ]);

            if (!File::exists($logFilePath)) {
                Log::error('Log file does not exist: ' . $logFilePath);
                return redirect()->route('settings.logs')->with('error', 'Log file does not exist.');
            }

            if (!File::isReadable($logFilePath)) {
                Log::error('Log file is not readable: ' . $logFilePath, [
                    'permissions' => substr(sprintf('%o', fileperms($logFilePath)), -4)
                ]);
                return redirect()->route('settings.logs')->with('error', 'Log file is not readable. Please check file permissions.');
            }

            $logContents = File::get($logFilePath);

            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="TIAS.log"',
            ];

            return Response::make($logContents, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting log file: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('settings.logs')->with('error', 'Error exporting log file: ' . $e->getMessage());
        }
    }

    public function clear()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');
            
            Log::info('Attempting to clear logs', [
                'user' => Auth::id() ?? 'unauthenticated',
                'file_path' => $logFilePath,
                'file_exists' => File::exists($logFilePath),
                'is_writable' => File::isWritable($logFilePath)
            ]);

            if (!File::exists($logFilePath)) {
                Log::warning('Log file not found when attempting to clear: ' . $logFilePath);
                return redirect()->route('settings.logs')
                    ->with('error', 'Log file does not exist.');
            }

            if (!File::isWritable($logFilePath)) {
                Log::error('Log file is not writable: ' . $logFilePath, [
                    'permissions' => substr(sprintf('%o', fileperms($logFilePath)), -4)
                ]);
                return redirect()->route('settings.logs')
                    ->with('error', 'Log file is not writable. Please check file permissions.');
            }

            File::put($logFilePath, '');
            Log::info('Logs cleared successfully');
            
            return redirect()->route('settings.logs')
                ->with('success', 'All logs have been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to clear logs: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('settings.logs')
                ->with('error', 'Failed to clear logs: ' . $e->getMessage());
        }
    }
}