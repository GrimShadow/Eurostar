<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsHeartbeat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class StatusBanner extends Component
{
    public $showBanner = false;
    public $message = '';
    public $status = '';
    
    #[On('echo:gtfs,HeartbeatReceived')]
    public function handleHeartbeat()
    {
        $this->checkStatus();
    }

    public function getListeners()
    {
        return [
            '$refresh',
            'echo:gtfs,HeartbeatReceived' => 'handleHeartbeat'
        ];
    }

    public function checkStatus()
    {
        $lastHeartbeat = GtfsHeartbeat::orderBy('id', 'desc')->first();
        
        if (!$lastHeartbeat) {
            $this->showBanner = true;
            $this->message = 'No real time updates available';
            $this->status = 'error';
            return;
        }

        $minutesSinceLastUpdate = Carbon::parse($lastHeartbeat->last_update_sent_timestamp)->diffInMinutes(now());
        
        if ($minutesSinceLastUpdate > 2) {
            $this->showBanner = true;
            $this->message = 'No real time updates available';
            $this->status = 'error';
        } else {
            $this->showBanner = false;
            $this->message = '';
            $this->status = '';
        }
    }

    public function render()
    {
        $this->checkStatus();
        return view('livewire.status-banner');
    }
}