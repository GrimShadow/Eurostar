<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceMode = Setting::where('key', 'maintenance_mode')->value('value') ?? false;

        if ($maintenanceMode && !auth()->user()->hasRole('admin')) {
            if ($request->is('logout') || $request->is('login')) {
                return $next($request);
            }
            return response()->view('maintenance');
        }

        return $next($request);
    }
} 