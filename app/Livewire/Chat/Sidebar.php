<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Support\Avatar;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Sidebar extends Component
{
    public $items = [];

    protected $listeners = [
        'refresh-sidebar' => '$refresh',
        'message-arrived' => 'load',
    ];

    public function mount()
    {
        $this->load();
    }

    public function load()
    {
        $me = Auth::id();
        $convs = Conversation::query()
            ->where(fn($q) => $q->where('user_one_id',$me)->orWhere('user_two_id',$me))
            ->with('lastMessage')
            ->orderByDesc('last_message_id')
            ->limit(100)
            ->get();

        $this->items = $convs->map(function($c) use ($me){
            $partnerId = $c->otherUserId($me);
            $partner   = \App\Models\User::with('anotherDetail')->find($partnerId);
            return [
                'id' => $c->id,
                'partner' => [
                    'id' => $partner?->id,
                    'name' => $partner?->name ?? trim(($partner->first_name ?? '').' '.($partner->last_name ?? '')),
                    'avatar' => Avatar::url($partner),
                ],
                'last' => $c->lastMessage?->body ?: $c->lastMessage?->file_name,
                'time' => optional($c->lastMessage?->created_at)?->diffForHumans(),
            ];
        })->values()->all();
    }

    public function open(int $conversationId)
    {
        $this->dispatch('open-conversation', conversationId: $conversationId)->to(Index::class);
    }

    public function render()
    {
        return view('livewire.chat.sidebar');
    }
}
