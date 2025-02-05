<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsRoute;
use App\Models\SelectedRoute;

class RouteSelector extends Component
{
    public $selectedRoutes = [];
    
    public function mount()
    {
        $this->selectedRoutes = SelectedRoute::where('is_active', true)
            ->pluck('route_id')
            ->toArray();
    }

    public function toggleRoute($routeId)
    {
        if (in_array($routeId, $this->selectedRoutes)) {
            SelectedRoute::where('route_id', $routeId)->delete();
            $this->selectedRoutes = array_diff($this->selectedRoutes, [$routeId]);
        } else {
            SelectedRoute::create(['route_id' => $routeId]);
            $this->selectedRoutes[] = $routeId;
        }
    }

    public function render()
    {
        return view('livewire.route-selector', [
            'routes' => GtfsRoute::orderBy('route_long_name')->get()
        ]);
    }
}
