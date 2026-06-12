<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoteStaffScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $pairingCode,
        public $barcode
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('staff-scan.'.$this->pairingCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'staff.scanned';
    }
}
