<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('chat.conversation.'.$this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'chat.new';
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
                'mime' => $this->message->file_mime ?? $this->message->mime_type ?? null,
                'url'  => $this->message->publicUrl(), // method below in model
                'is_image' => $this->message->isImage(),
            ],
            'created_at'      => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}
