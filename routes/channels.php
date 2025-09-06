<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conv = Conversation::find($conversationId);
    return $conv && $conv->hasUser($user->id);
});

// Presence to enable typing/here/joining/leaving (no secrets)
Broadcast::channel('presence.conversation.{conversationId}', function ($user, $conversationId) {
    $conv = Conversation::find($conversationId);
    if (! $conv || ! $conv->hasUser($user->id)) return false;

    return [
        'id'   => $user->id,
        'name' => $user->name ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
        'avatar' => \App\Support\Avatar::url($user),
    ];
});

// Simple presence channel per-user so others can detect "online"
Broadcast::channel('presence.user.{userId}', function ($auth, $userId) {
    // allow any logged-in user to join any user's presence room (carries no data beyond presence)
    return (bool) $auth->id;
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conv = Conversation::find($conversationId);
    if (! $conv || ! $conv->hasUser($user->id)) return false;

    // Return presence member info:
    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => function_exists('user_avatar_url') ? user_avatar_url($user) : null,
    ];
});

Broadcast::channel('presence-user.{userId}', function ($user, $userId) {
    // Only let yourself join your own presence-room
    if ((int)$userId !== (int)$user->id) return false;
    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('user.{userId}', function ($auth, $userId) {
    return $auth && $auth->id ? ['id' => (int) $auth->id] : false;
});