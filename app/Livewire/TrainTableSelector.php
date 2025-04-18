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
        $setting = Setting::where('key', 'train_table_routes')->first();
        $this->selectedRoutes = $setting ? $setting->value : [];
    }

    public function toggleTableRoute($routeId)
    {
        if (in_array($routeId, $this->selectedRoutes)) {
            $this->selectedRoutes = array_diff($this->selectedRoutes, [$routeId]);
            DB::table('train_table_routes')
                ->where('route_id', $routeId)
                ->update(['is_active' => false]);
        } else {
            $this->selectedRoutes[] = $routeId;
            DB::table('train_table_routes')
                ->updateOrInsert(
                    ['route_id' => $routeId],
                    ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
        }
    }

    public function render()
    {
        return view('livewire.train-table-selector');
    }
} 