<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Group;

class GroupAccess
{
    public function handle(Request $request, Closure $next)
    {
        $group = $request->route('group');
        

        if (!$group) {
            Log::error('Group not found');
            abort(404, 'Group not found');
        }

        if (!$group->users->contains(Auth::id())) {
            Log::error('User does not have access to group', [
                'user_id' => Auth::id(),
                'group_id' => $group->id
            ]);
            abort(403, 'You do not have access to this group');
        }

        // Share the group with all views
        view()->share('currentGroup', $group);

        return $next($request);
    }
} 