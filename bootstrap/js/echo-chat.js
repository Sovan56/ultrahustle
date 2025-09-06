// bootstrap/js/echo-chat.js
// Loads Echo if not already set, and exposes helpers to subscribe to channels.

import Echo from 'laravel-echo';

if (typeof window.Echo === 'undefined') {
  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
  });
}

window.ChatEcho = {
  // Private conversation events (message.new / delivered / seen)
  subscribeConversation(convId, handlers = {}) {
    const ch = window.Echo.private(`conversation.${convId}`);
    ch.listen('.message.new', (e) => handlers.onNew && handlers.onNew(e));
    ch.listen('.message.delivered', (e) => handlers.onDelivered && handlers.onDelivered(e));
    ch.listen('.message.seen', (e) => handlers.onSeen && handlers.onSeen(e));
    return ch;
  },

  // Presence conversation for typing (client events "whisper")
  subscribePresence(convId, handlers = {}) {
    const ch = window.Echo.join(`presence.conversation.${convId}`)
      .here((members) => handlers.onHere && handlers.onHere(members))
      .joining((m) => handlers.onJoin && handlers.onJoin(m))
      .leaving((m) => handlers.onLeave && handlers.onLeave(m))
      .listenForWhisper('typing', (payload) => handlers.onTyping && handlers.onTyping(payload));
    return ch;
  },

  // Global presence per-user to reflect online dot anywhere
  subscribeUserPresence(userId, handlers = {}) {
    // we're not using members here; just knowing anyone joined => user is online if it's their room
    const ch = window.Echo.join(`presence.user.${userId}`)
      .here((members) => handlers.onOnlineChange && handlers.onOnlineChange(members.length > 0))
      .joining(() => handlers.onOnlineChange && handlers.onOnlineChange(true))
      .leaving((members) => handlers.onOnlineChange && handlers.onOnlineChange(members.length > 0));
    return ch;
  },
};
