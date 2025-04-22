<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class LogsController extends Controller
{
    public function viewLogs()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');
            
            if (!File::exists($logFilePath)) {
                Log::warning('Log file not found at: ' . $logFilePath);
                return view('settings.logs', ['logContents' => 'No log file found.']);
            }

            if (!File::isReadable($logFilePath)) {
                Log::error('Log file is not readable: ' . $logFilePath);
                return view('settings.logs', ['logContents' => 'Log file is not readable.']);
            }

            $logContents = File::get($logFilePath);
            return view('settings.logs', compact('logContents'));
        } catch (\Exception $e) {
            Log::error('Error accessing log file: ' . $e->getMessage());
            return view('settings.logs', ['logContents' => 'Error accessing log file: ' . $e->getMessage()]);
        }
    }

    public function exportLogs()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');

            if (!File::exists($logFilePath)) {
                Log::error('Log file does not exist: ' . $logFilePath);
                return redirect()->route('settings.logs')->with('error', 'Log file does not exist.');
            }

            if (!File::isReadable($logFilePath)) {
                Log::error('Log file is not readable: ' . $logFilePath);
                return redirect()->route('settings.logs')->with('error', 'Log file is not readable.');
            }

            $logContents = File::get($logFilePath);

            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="TIAS.log"',
            ];

            return Response::make($logContents, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting log file: ' . $e->getMessage());
            return redirect()->route('settings.logs')->with('error', 'Error exporting log file: ' . $e->getMessage());
        }
    }

    public function clear()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');
            
            if (!File::exists($logFilePath)) {
                Log::warning('Log file not found when attempting to clear: ' . $logFilePath);
                return redirect()->route('settings.logs')
                    ->with('error', 'Log file does not exist.');
            }

            if (!File::isWritable($logFilePath)) {
                Log::error('Log file is not writable: ' . $logFilePath);
                return redirect()->route('settings.logs')
                    ->with('error', 'Log file is not writable.');
            }

            File::put($logFilePath, '');
            Log::info('Logs cleared successfully');
            
            return redirect()->route('settings.logs')
                ->with('success', 'All logs have been cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to clear logs: ' . $e->getMessage());
            return redirect()->route('settings.logs')
                ->with('error', 'Failed to clear logs: ' . $e->getMessage());
        }
    }
}