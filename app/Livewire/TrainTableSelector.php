<?php

namespace App\Livewire;

use App\Models\GtfsRoute;
use App\Models\Setting;
use Livewire\Component;

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

    public function toggleRoute($routeId)
    {
        if (in_array($routeId, $this->selectedRoutes)) {
            $this->selectedRoutes = array_diff($this->selectedRoutes, [$routeId]);
        } else {
            $this->selectedRoutes[] = $routeId;
        }
        
        Setting::updateOrCreate(
            ['key' => 'train_table_routes'],
            ['value' => $this->selectedRoutes]
        );
    }

    public function render()
    {
        return view('livewire.train-table-selector');
    }
} 