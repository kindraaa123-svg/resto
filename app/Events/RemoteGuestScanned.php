<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoteGuestScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $requesterId,
        public $barcode
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('guest-result.'.$this->requesterId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'guest.scanned';
    }
}
