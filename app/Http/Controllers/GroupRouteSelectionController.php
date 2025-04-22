<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupRouteSelection;
use App\Models\GroupTrainTableSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GroupRouteSelectionController extends Controller
{
    public function index(Group $group)
    {
        $selectedRoutes = $group->routeSelections()
            ->with('route')
            ->where('is_active', true)
            ->get();

        $selectedTrainTableRoutes = $group->trainTableSelections()
            ->with('route')
            ->where('is_active', true)
            ->get();

        return view('group.route-selection', compact('group', 'selectedRoutes', 'selectedTrainTableRoutes'));
    }

    public function updateRouteSelection(Request $request, Group $group)
    {
        $request->validate([
            'route_id' => 'required|string',
            'is_active' => 'required|boolean'
        ]);

        GroupRouteSelection::updateOrCreate(
            [
                'group_id' => $group->id,
                'route_id' => $request->route_id
            ],
            ['is_active' => $request->is_active]
        );

        Log::info('Group route selection updated', [
            'group_id' => $group->id,
            'route_id' => $request->route_id,
            'is_active' => $request->is_active
        ]);

        return response()->json(['success' => true]);
    }

    public function updateTrainTableSelection(Request $request, Group $group)
    {
        $request->validate([
            'route_id' => 'required|string',
            'is_active' => 'required|boolean'
        ]);

        GroupTrainTableSelection::updateOrCreate(
            [
                'group_id' => $group->id,
                'route_id' => $request->route_id
            ],
            ['is_active' => $request->is_active]
        );

        Log::info('Group train table selection updated', [
            'group_id' => $group->id,
            'route_id' => $request->route_id,
            'is_active' => $request->is_active
        ]);

        return response()->json(['success' => true]);
    }
} 