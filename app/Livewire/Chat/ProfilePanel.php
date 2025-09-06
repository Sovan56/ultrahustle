<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Support\Avatar;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfilePanel extends Component
{
    public ?int $conversationId = null;
    public $partner;

    protected $listeners = [
        'open-conversation' => 'openConversation',
    ];

    public function mount(?int $conversationId = null)
    {
        if ($conversationId) $this->openConversation($conversationId);
    }

    public function openConversation(int $conversationId)
    {
        $me = Auth::id();
        $conv = Conversation::find($conversationId);
        if (!$conv || !$conv->hasUser($me)) {
            $this->conversationId = null;
            $this->partner = null;
            return;
        }
        $this->conversationId = $conversationId;
        $partnerId = $conv->otherUserId($me);
        $this->partner = \App\Models\User::with('anotherDetail')->find($partnerId);
    }

    public function render()
    {
        return view('livewire.chat.profile-panel');
    }
}
