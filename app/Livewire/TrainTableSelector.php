<?php

namespace App\Livewire;

use App\Models\GtfsRoute;
use App\Models\Setting;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class TrainTableSelector extends Component
{
    public $selectedRoutes = [];
    public $routes;
    
    public function mount()
    {
        $this->routes = GtfsRoute::all();
        $this->loadSelectedRoutes();
    }

    public function loadSelectedRoutes()
    {
        $this->selectedRoutes = DB::table('train_table_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();
    }

    public function toggleTableRoute($routeId)
    {
        $route = DB::table('train_table_routes')
            ->where('route_id', $routeId)
            ->first();

        if ($route) {
            $newStatus = !$route->is_active;
            DB::table('train_table_routes')
                ->where('route_id', $routeId)
                ->update(['is_active' => $newStatus]);
        } else {
            DB::table('train_table_routes')
                ->insert([
                    'route_id' => $routeId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
        }

        $this->loadSelectedRoutes();
    }

    public function render()
    {
        return view('livewire.train-table-selector');
    }
} 