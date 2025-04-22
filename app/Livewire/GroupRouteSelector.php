<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\GroupRouteSelection;
use App\Models\Route;
use Livewire\Component;

class GroupRouteSelector extends Component
{
    public Group $group;
    public $selectedRoutes = [];
    public $routes = [];

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->loadSelectedRoutes();
        $this->loadRoutes();
    }

    public function loadSelectedRoutes()
    {
        $this->selectedRoutes = $this->group->routeSelections()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();
    }

    public function loadRoutes()
    {
        $this->routes = Route::orderBy('route_short_name')->get();
    }

    public function toggleRoute($routeId)
    {
        $isSelected = in_array($routeId, $this->selectedRoutes);
        
        if ($isSelected) {
            $this->selectedRoutes = array_diff($this->selectedRoutes, [$routeId]);
        } else {
            $this->selectedRoutes[] = $routeId;
        }

        GroupRouteSelection::updateOrCreate(
            [
                'group_id' => $this->group->id,
                'route_id' => $routeId
            ],
            ['is_active' => !$isSelected]
        );
    }

    public function render()
    {
        return view('livewire.group-route-selector');
    }
} 