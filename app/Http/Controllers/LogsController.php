<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class LogsController extends Controller
{
    public function viewLogs()
    {
        $logContents = file_get_contents(storage_path('logs/laravel.log'));
        return view('settings.logs', compact('logContents'));
    }

    public function exportLogs()
    {
        $logFilePath = storage_path('logs/laravel.log');

        if (!File::exists($logFilePath)) {
            return redirect()->route('settings')->with('error', 'Log file does not exist.');
        }

        // Read the contents of the log file
        $logContents = File::get($logFilePath);

        // Create a downloadable response with the new filename
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="TIAS.log"',
        ];

        return Response::make($logContents, 200, $headers);
    }

    public function clear()
    {
        try {
            $logFilePath = storage_path('logs/laravel.log');
            if (File::exists($logFilePath)) {
                File::put($logFilePath, '');
            }
            return redirect()->route('settings.logs')
                ->with('success', 'All logs have been cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->route('settings.logs')
                ->with('error', 'Failed to clear logs: ' . $e->getMessage());
        }
    }
}