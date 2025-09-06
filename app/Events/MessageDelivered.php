<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDelivered implements ShouldBroadcastNow
{
    public function __construct(public int $conversationId, public ?int $messageId = null) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('chat.conversation.'.$this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'chat.delivered';
    }

     public function broadcastWith(): array
 {
     return [
         'conversation_id'   => $this->conversationId,
         'message_id'        => $this->messageId,
         'last_delivered_id' => $this->messageId,
     ];
 }

}
