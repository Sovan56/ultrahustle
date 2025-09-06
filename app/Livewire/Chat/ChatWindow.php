<?php

namespace App\Livewire\Chat;

use App\Events\NewMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Support\Avatar;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatWindow extends Component
{
    use WithFileUploads;

    public ?int $conversationId = null;
    public $messages = [];
    public $body = '';
    public $file;
    public $me;
    public $partner;
    public $fromService = false;

    // local UI states
    public bool $partnerOnline = false;
    public bool $partnerTyping = false;

    protected $listeners = [
        'open-conversation' => 'openConversation',
        'chat-js:new-message' => 'ingestNewMessage',
        'chat-js:delivered'   => 'markDeliveredLocal',
        'chat-js:seen'        => 'markSeenLocal',
        'chat-js:online'      => 'setOnline',
        'chat-js:typing'      => 'setTyping',
    ];

    public function mount(?int $conversationId = null)
    {
        $this->me = Auth::user();
        if ($conversationId) $this->openConversation($conversationId);
    }

    public function openConversation(int $conversationId)
    {
        $conv = Conversation::find($conversationId);
        if (!$conv || !$conv->hasUser($this->me->id)) {
            $this->conversationId = null;
            $this->messages = [];
            $this->partner = null;
            return;
        }

        $this->conversationId = $conv->id;

        $partnerId = $conv->otherUserId($this->me->id);
        $this->partner = \App\Models\User::with('anotherDetail')->find($partnerId);

        $this->fromService = (bool) (($conv->meta['from_service'] ?? false));

        $this->messages = Message::where('conversation_id', $conv->id)
            ->orderBy('id')
            ->limit(300)
            ->get()
            ->map(fn($m) => $this->asArr($m))
            ->all();

        // mark delivered & seen on open
        // mark delivered & seen on open
        $this->js("fetch('" . route('chat.delivered', $conv->id) . "',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector(\"meta[name='csrf-token']\").content}})");
        $this->js("fetch('" . route('chat.seen', $conv->id) . "',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector(\"meta[name='csrf-token']\").content}})");

        $this->dispatch('message-arrived')->to(Sidebar::class);
    }

    protected function asArr(Message $m): array
    {
        return [
            'id'   => $m->id,
            'me'   => $m->sender_id === $this->me->id,
            'body' => $m->body,
            'file' => $m->file_path ? [
                'url'  => asset('storage/' . $m->file_path),
                'name' => $m->file_name,
                'size' => $m->file_size,
                'mime' => $m->mime_type,
                'is_image' => str_starts_with(strtolower($m->mime_type ?? ''), 'image/'),
            ] : null,
            'status' => $m->status,
            'time'   => $m->created_at?->diffForHumans(),
            'avatar' => $m->sender_id === $this->me->id ? Avatar::url($this->me) : Avatar::url($this->partner),
        ];
    }

    public function send()
    {
        if (!$this->conversationId) return;

        $this->validate([
            'body' => ['nullable', 'string', 'max:10000', function ($attr, $value, $fail) {
                if (!$value) return;
                if (preg_match('/\b[\w\.-]+@[\w\.-]+\.\w{2,}\b/i', $value)) $fail('Email not allowed.');
                if (preg_match('/\+?\d[\d\-\s()]{7,}\d/', $value)) $fail('Phone number not allowed.');
            }],
            'file' => ['nullable', 'file', 'max:5120000'],
        ]);

        if (!$this->body && !$this->file) return;

        $filePath = $fileName = $mime = null;
        $fileSize = null;
        if ($this->file) {
            $mime = $this->file->getMimeType();
            $fileSize = $this->file->getSize();
            $fileName = $this->file->getClientOriginalName();
            $filePath = $this->file->store('chat', 'public');
        }

        $msg = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->me->id,
            'body' => $this->body ?: null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mime,
            'status' => 'sent',
        ]);

        Conversation::where('id', $this->conversationId)->update(['last_message_id' => $msg->id]);

        $this->messages[] = $this->asArr($msg);
        $this->dispatch('message-arrived')->to(Sidebar::class);

        $this->reset(['body', 'file']);

        broadcast(new \App\Events\NewMessage($msg))->toOthers();
    }

    /** JS → Livewire: new message came via Echo */
    public function ingestNewMessage(array $payload)
    {
        if (!$this->conversationId) return;
        if ((int)$payload['conversation_id'] !== (int)$this->conversationId) return;

        $m = Message::find($payload['id']);
        if ($m) {
            $this->messages[] = $this->asArr($m);
        }
    }

    public function markDeliveredLocal() {}
    public function markSeenLocal(array $payload = [])
    {
        // mark all my-sent messages as seen locally if last_seen_id given
        if (isset($payload['last_seen_id'])) {
            $last = (int)$payload['last_seen_id'];
            foreach ($this->messages as &$m) {
                if ($m['me'] && $m['status'] !== 'seen' && $m['id'] <= $last) {
                    $m['status'] = 'seen';
                }
            }
        }
    }

    public function setOnline(bool $online)
    {
        $this->partnerOnline = $online;
    }
    public function setTyping(array $p)
    {
        $this->partnerTyping = (bool)($p['typing'] ?? false);
    }

    public function render()
    {
        return view('livewire.chat.chat-window');
    }
}
