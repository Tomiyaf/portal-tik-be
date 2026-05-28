<?php

namespace App\Events;

use App\Models\AccessLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GateAccessRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AccessLog $accessLog
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('gate-access');
    }

    public function broadcastAs(): string
    {
        return 'gate.access.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->accessLog->id,
            'gate_id' => $this->accessLog->gate_id,
            'user_id' => $this->accessLog->user_id,
            'access_status' => $this->accessLog->access_status,
            'access_method' => $this->accessLog->access_method,
            'action' => $this->accessLog->action,
            'notes' => $this->accessLog->notes,
            'created_at' => $this->accessLog->created_at,
        ];
    }
}