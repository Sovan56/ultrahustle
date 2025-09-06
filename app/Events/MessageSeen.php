<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSeen implements ShouldBroadcastNow
{
    public function __construct(public int $conversationId, public ?int $lastSeenMessageId) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('chat.conversation.'.$this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'chat.seen';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'last_seen_id'    => $this->lastSeenMessageId,
        ];
    }
}
