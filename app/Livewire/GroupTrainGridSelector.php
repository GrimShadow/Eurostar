<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\GtfsRoute;
use App\Models\GtfsStop;
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
            $route->update(['is_active' => ! $route->is_active]);
            if (! $route->is_active) {
                // Remove all stations for this route if route is deselected
                $this->group->routeStations()
                    ->where('route_id', $routeId)
                    ->update(['is_active' => false]);
            }
        } else {
            $this->group->selectedRoutes()->create([
                'route_id' => $routeId,
                'is_active' => true,
            ]);
        }

        $this->loadSelectedRoutes();
        $this->loadSelectedStations();
    }

    public function toggleStation($routeId, $stopId)
    {
        // Check if this is a generic stop ID (created by our grouping logic)
        $isGenericStop = ! $this->isSpecificPlatformStop($stopId);

        if ($isGenericStop) {
            // For generic stops, we need to find all platform-specific stops for this station
            $stationName = $this->getStationNameFromStopId($stopId, $routeId);
            $platformStops = $this->getPlatformStopsForStation($stationName, $routeId);

            // Check if any platform stop is currently active
            $hasActivePlatform = $this->group->routeStations()
                ->where('route_id', $routeId)
                ->whereIn('stop_id', $platformStops->pluck('stop_id'))
                ->where('is_active', true)
                ->exists();

            $newStatus = ! $hasActivePlatform;

            // Update all platform stops for this station
            foreach ($platformStops as $platformStop) {
                $station = $this->group->routeStations()
                    ->where('route_id', $routeId)
                    ->where('stop_id', $platformStop->stop_id)
                    ->first();

                if ($station) {
                    $station->update(['is_active' => $newStatus]);
                } else {
                    $this->group->routeStations()->create([
                        'route_id' => $routeId,
                        'stop_id' => $platformStop->stop_id,
                        'is_active' => $newStatus,
                    ]);
                }
            }
        } else {
            // Handle specific platform stops as before
            $station = $this->group->routeStations()
                ->where('route_id', $routeId)
                ->where('stop_id', $stopId)
                ->first();

            if ($station) {
                $station->update(['is_active' => ! $station->is_active]);
            } else {
                $this->group->routeStations()->create([
                    'route_id' => $routeId,
                    'stop_id' => $stopId,
                    'is_active' => true,
                ]);
            }
        }

        $this->loadSelectedStations();
    }

    private function isSpecificPlatformStop($stopId)
    {
        // Check if this is a platform-specific stop (has platform suffix)
        return preg_match('/_\d+[a-z]?$/', $stopId);
    }

    private function getStationNameFromStopId($stopId, $routeId)
    {
        // Get the station name for this stop ID
        $stop = GtfsStop::whereHas('stopTimes', function ($query) use ($routeId) {
            $query->whereHas('trip', function ($query) use ($routeId) {
                $query->where('route_id', $routeId);
            });
        })
            ->where('stop_id', 'like', $stopId.'%')
            ->first();

        return $stop ? $stop->stop_name : null;
    }

    private function getPlatformStopsForStation($stationName, $routeId)
    {
        return GtfsStop::whereHas('stopTimes', function ($query) use ($routeId) {
            $query->whereHas('trip', function ($query) use ($routeId) {
                $query->where('route_id', $routeId);
            });
        })
            ->where('stop_name', $stationName)
            ->get();
    }

    public function getStationsForRoute($routeId)
    {
        // Get all stops for this route
        $stops = GtfsStop::whereHas('stopTimes', function ($query) use ($routeId) {
            $query->whereHas('trip', function ($query) use ($routeId) {
                $query->where('route_id', $routeId);
            });
        })
            ->orderBy('stop_name')
            ->get();

        // Group stops by station name to avoid duplicates from different platforms
        $groupedStops = $stops->groupBy('stop_name')->map(function ($stationStops) {
            // If there's a main station entry (no platform code), use that
            $mainStation = $stationStops->where('platform_code', '')->first();
            if ($mainStation) {
                return $mainStation;
            }

            // Otherwise, use the first platform entry but update the stop_id to be more generic
            $firstStop = $stationStops->first();
            // Create a generic stop_id by removing platform-specific suffixes
            $genericStopId = preg_replace('/_\d+[a-z]?$/', '', $firstStop->stop_id);

            // Clone the stop and update the stop_id to be generic
            $genericStop = clone $firstStop;
            $genericStop->stop_id = $genericStopId;

            return $genericStop;
        });

        return $groupedStops->values();
    }

    public function toggleRouteExpansion($routeId)
    {
        $this->expandedRoute = $this->expandedRoute === $routeId ? null : $routeId;
    }

    public function render()
    {
        $routes = GtfsRoute::query()
            ->when($this->search, function ($query) {
                $query->where('route_long_name', 'like', '%'.$this->search.'%')
                    ->orWhere('route_short_name', 'like', '%'.$this->search.'%');
            })
            ->orderBy('route_long_name')
            ->get();

        return view('livewire.group-train-grid-selector', [
            'routes' => $routes,
        ]);
    }
}
