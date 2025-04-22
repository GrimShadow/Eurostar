<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\GtfsRoute;
use Livewire\Component;

class GroupTrainGridSelector extends Component
{
    public Group $group;
    public $selectedRoutes = [];
    public $search = '';

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->loadSelectedRoutes();
    }

    public function loadSelectedRoutes()
    {
        $this->selectedRoutes = $this->group->selectedRoutes()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();
    }

    public function toggleRoute($routeId)
    {
        $route = $this->group->selectedRoutes()->where('route_id', $routeId)->first();

        if ($route) {
            $route->update(['is_active' => !$route->is_active]);
        } else {
            $this->group->selectedRoutes()->create([
                'route_id' => $routeId,
                'is_active' => true
            ]);
        }

        $this->loadSelectedRoutes();
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