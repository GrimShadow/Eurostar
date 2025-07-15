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

        // Check if the heartbeat itself is recent (within 5 minutes)
        $minutesSinceHeartbeat = Carbon::parse($lastHeartbeat->created_at)->diffInMinutes(now());
        
        // If status is "up" and heartbeat is recent, don't show banner
        if ($lastHeartbeat->status === 'up' && $minutesSinceHeartbeat <= 5) {
            $this->showBanner = false;
            $this->message = '';
            $this->status = '';
        } else {
            // Show banner if status is not "up" or heartbeat is too old
            $this->showBanner = true;
            $this->message = 'No real time updates available';
            $this->status = 'error';
        }
    }

    public function render()
    {
        $this->checkStatus();
        return view('livewire.status-banner');
    }
}