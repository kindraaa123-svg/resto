<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoteGuestDiscovery implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $networkId,
        public $deviceId,
        public $deviceName
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('guest-discovery.'.$this->networkId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'guest.discovery';
    }
}
