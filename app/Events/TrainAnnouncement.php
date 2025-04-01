<?php

namespace App\Events;

use App\Models\GtfsTrip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrainAnnouncement implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $train;
    public $message;

    public function __construct(GtfsTrip $train, string $message)
    {
        $this->train = $train;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('train-announcements');
    }
} 