<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    public function __construct(public Message $message) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('presence-chat.conversation.' . $this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'chat.new'; // same as NewMessage, keep consistent
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id'       => $this->message->sender_id,
            'body'            => $this->message->body,
            'file'            => [
                'name' => $this->message->file_name,
                'size' => $this->message->file_size,
                'mime' => $this->message->mime_type,
                'url'  => $this->message->publicUrl(),
                'is_image' => $this->message->isImage(),
            ],
            'status'     => $this->message->status,
            'created_at' => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}
