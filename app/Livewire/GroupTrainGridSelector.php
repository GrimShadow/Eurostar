<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\GtfsRoute;
use App\Models\GtfsStop;
use App\Models\GtfsStopTime;
use Livewire\Component;

class GroupTrainGridSelector extends Component
{
    public Group $group;
    public $selectedRoutes = [];
    public $selectedStations = [];
    public $search = '';
    public $expandedRoute = null;

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->loadSelectedRoutes();
        $this->loadSelectedStations();
    }

    public function loadSelectedRoutes()
    {
        $this->selectedRoutes = $this->group->selectedRoutes()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();
    }

    public function loadSelectedStations()
    {
        $this->selectedStations = $this->group->routeStations()
            ->where('is_active', true)
            ->get()
            ->groupBy('route_id')
            ->map(function ($stations) {
                return $stations->pluck('stop_id')->toArray();
            })
            ->toArray();
    }

    public function toggleRoute($routeId)
    {
        $route = $this->group->selectedRoutes()->where('route_id', $routeId)->first();

        if ($route) {
            $route->update(['is_active' => !$route->is_active]);
            if (!$route->is_active) {
                // Remove all stations for this route if route is deselected
                $this->group->routeStations()
                    ->where('route_id', $routeId)
                    ->update(['is_active' => false]);
            }
        } else {
            $this->group->selectedRoutes()->create([
                'route_id' => $routeId,
                'is_active' => true
            ]);
        }

        $this->loadSelectedRoutes();
        $this->loadSelectedStations();
    }

    public function toggleStation($routeId, $stopId)
    {
        $station = $this->group->routeStations()
            ->where('route_id', $routeId)
            ->where('stop_id', $stopId)
            ->first();

        if ($station) {
            $station->update(['is_active' => !$station->is_active]);
        } else {
            $this->group->routeStations()->create([
                'route_id' => $routeId,
                'stop_id' => $stopId,
                'is_active' => true
            ]);
        }

        $this->loadSelectedStations();
    }

    public function getStationsForRoute($routeId)
    {
        return GtfsStop::whereHas('stopTimes', function ($query) use ($routeId) {
            $query->whereHas('trip', function ($query) use ($routeId) {
                $query->where('route_id', $routeId);
            });
        })
        ->orderBy('stop_name')
        ->get();
    }

    public function toggleRouteExpansion($routeId)
    {
        $this->expandedRoute = $this->expandedRoute === $routeId ? null : $routeId;
    }

    public function render()
    {
        $routes = GtfsRoute::query()
            ->when($this->search, function ($query) {
                $query->where('route_long_name', 'like', '%' . $this->search . '%')
                    ->orWhere('route_short_name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('route_long_name')
            ->get();

        return view('livewire.group-train-grid-selector', [
            'routes' => $routes
        ]);
    }
} 