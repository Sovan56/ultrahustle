<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserTyping implements ShouldBroadcastNow
{
    use InteractsWithSockets;
    public function __construct(public int $conversationId, public int $userId, public bool $isTyping) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('chat.conversation.'.$this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'chat.typing';
    }

    public function broadcastWith(): array
    {
        return ['conversation_id'=>$this->conversationId, 'user_id'=>$this->userId, 'typing'=>$this->isTyping];
    }
}
