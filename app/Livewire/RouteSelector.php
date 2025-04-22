<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsRoute;
use App\Models\SelectedRoute;
use Illuminate\Support\Facades\DB;

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
        $route = DB::table('selected_routes')
            ->where('route_id', $routeId)
            ->first();

        if ($route) {
            $newStatus = !$route->is_active;
            DB::table('selected_routes')
                ->where('route_id', $routeId)
                ->update(['is_active' => $newStatus]);
        } else {
            DB::table('selected_routes')
                ->insert([
                    'route_id' => $routeId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
        }

        $this->selectedRoutes = SelectedRoute::where('is_active', true)
            ->pluck('route_id')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.route-selector', [
            'routes' => GtfsRoute::orderBy('route_long_name')->get()
        ]);
    }
}
