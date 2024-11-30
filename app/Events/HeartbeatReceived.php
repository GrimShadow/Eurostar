<?php

namespace App\Events;

use App\Models\GtfsHeartbeat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HeartbeatReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $heartbeat;

    public function __construct(GtfsHeartbeat $heartbeat)
    {
        $this->heartbeat = $heartbeat;
    }

    public function broadcastOn()
    {
        return new Channel('gtfs');
    }
}