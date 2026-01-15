<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealtimeConflictDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tripId;

    public $stopId;

    public $fieldType;

    public $manualValue;

    public $realtimeValue;

    public $conflictId;

    public $manualUserId;

    public function __construct($tripId, $stopId, $fieldType, $manualValue, $realtimeValue, $conflictId, $manualUserId)
    {
        $this->tripId = $tripId;
        $this->stopId = $stopId;
        $this->fieldType = $fieldType;
        $this->manualValue = $manualValue;
        $this->realtimeValue = $realtimeValue;
        $this->conflictId = $conflictId;
        $this->manualUserId = $manualUserId;
    }

    public function broadcastOn()
    {
        return new Channel('realtime-conflicts');
    }
}
