<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class Index extends Component
{
    public ?int $openConversationId = null;

    protected $listeners = [
        'open-conversation' => 'openConversation',
    ];

    public function mount(?int $openConversationId = null)
    {
        $this->openConversationId = $openConversationId;
    }

    public function openConversation(int $conversationId)
    {
        $this->openConversationId = $conversationId;
    }

    public function render()
    {
        return view('livewire.chat.index');
    }
}
