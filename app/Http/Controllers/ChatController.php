<?php

namespace App\Http\Controllers;

use App\Events\MessageDelivered;
use App\Events\MessageSeen;
use App\Events\NewMessage;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    /** Messages page (Otika) */
    public function page(Request $request)
    {
        $me = Auth::id();
        abort_unless($me, 403);

        $partnerId = (int) $request->query('partner', 0);
        $productId = (int) $request->query('product', 0);
        $isService = (bool) $request->query('is_service', false);

        $openConversationId = null;

        if ($partnerId > 0 && $partnerId !== $me) {
            $conv = Conversation::pair($me, $partnerId);
            if ($isService) {
                $meta = $conv->meta ?? [];
                $meta['from_service'] = true;
                if ($productId) $meta['origin_product_id'] = $productId;
                $conv->meta = $meta;
                $conv->save();
            }
            $openConversationId = $conv->id;
        }

        return view('UserAdmin.messages', compact('openConversationId'));
    }

    /** Build a safe display name from the users table */
    private function displayName(?User $u): string
    {
        if (!$u) return 'User';
        $first = trim((string)($u->first_name ?? ''));
        $last  = trim((string)($u->last_name ?? ''));
        $full  = trim($first . ' ' . $last);
        if ($full !== '') return $full;
        if (!empty($u->email)) return $u->email;
        return (string)($u->unique_id ?? 'User');
    }

    /** Resolve avatar & details row using unique_id mapping in user_admin_another_details */
    private function profileRowForUser(?User $u)
    {
        if (!$u || empty($u->unique_id)) return null;
        return DB::table('user_admin_another_details')
            ->where('user_admin_id', $u->unique_id)
            ->first();
    }

    /** Normalize avatar URL through media.pass */
    private function avatarUrl(?User $u): ?string
    {
        $det = $this->profileRowForUser($u);
        if ($det && $det->profile_picture) {
            $path = ltrim($det->profile_picture, '/');
            if (str_starts_with($path, 'storage/')) $path = substr($path, 8);
            if (str_starts_with($path, 'public/'))  $path = substr($path, 7);
            return route('media.pass', ['path' => $path]);
        }
        if (function_exists('user_avatar_url')) {
            return user_avatar_url($u);
        }
        return null;
    }

    /** Country name (by countries table id) */
    private function countryName(?int $id): ?string
    {
        if (!$id) return null;
        return DB::table('countries')->where('id', $id)->value('name');
    }

    /** =========================
     *  Chat list (left pane)
     *  ========================= */
    public function conversations(Request $request)
    {
        $me = Auth::id();
        abort_unless($me, 403);

        $table = (new Conversation)->getTable();
        $cols  = Schema::getColumnListing($table);
        $usesUserPair    = in_array('user_one_id', $cols) && in_array('user_two_id', $cols);
        $usesBuyerSeller = in_array('buyer_id', $cols) && in_array('seller_id', $cols);

        $q = Conversation::query();
        if ($usesUserPair) {
            $q->where(function ($w) use ($me) {
                $w->where('user_one_id', $me)->orWhere('user_two_id', $me);
            });
        }
        if ($usesBuyerSeller) {
            $q->orWhere(function ($w) use ($me) {
                $w->where('buyer_id', $me)->orWhere('seller_id', $me);
            });
        }

        $convs = $q->orderByDesc('last_message_id')
            ->orderByDesc('updated_at')
            ->limit(100)->get();

        $items = [];
        foreach ($convs as $c) {
            $otherId = $c->otherParticipantId($me);
            if (!$otherId) continue;

            $u = User::select('id', 'unique_id', 'first_name', 'last_name', 'email', 'created_at', 'country_id', 'last_seen_at')
                ->find($otherId);

            $avatar = $this->avatarUrl($u);
            $last   = null;

            if ($c->last_message_id) {
                $m = Message::find($c->last_message_id);
            } else {
                $m = Message::where('conversation_id', $c->id)->latest('id')->first();
            }
            if ($m) {
                $prev = $m->body ?: ($m->file_name ? ('📎 ' . $m->file_name) : '—');
                $last = [
                    'id'         => $m->id,
                    'preview'    => mb_strimwidth($prev, 0, 64, '…'),
                    'created_at' => optional($m->created_at)->toIso8601String(),
                ];
            }

            // unread count
            $unread = 0;
            $mr = MessageRead::where('conversation_id', $c->id)->where('user_id', $me)->first();
            if ($mr?->last_read_message_id) {
                $unread = Message::where('conversation_id', $c->id)
                    ->where('sender_id', '!=', $me)
                    ->where('id', '>', $mr->last_read_message_id)
                    ->count();
            } else {
                $unread = Message::where('conversation_id', $c->id)
                    ->where('sender_id', '!=', $me)
                    ->count();
            }

            // ✅ FIX: Only mark online if last_seen_at < 2 minutes ago
            $online = $u?->last_seen_at instanceof \Carbon\Carbon
    ? $u->last_seen_at->isAfter(now()->subSeconds(60))
    : false;


            $items[] = [
                'id' => $c->id,
                'partner' => [
                    'id'           => $u?->id,
                    'name'         => $this->displayName($u),
                    'avatar'       => $avatar,
                    'online'       => (bool) $online,
                    'last_seen_at' => optional($u?->last_seen_at)->toIso8601String(),
                ],
                'last' => $last,
                'unread' => $unread,
                'from_service' => (bool) (($c->meta['from_service'] ?? $c->from_service ?? false) ? 1 : 0),
            ];
        }

        // Sort by last.created_at (desc; nulls last)
        usort($items, function ($a, $b) {
            $ta = $a['last']['created_at'] ?? null;
            $tb = $b['last']['created_at'] ?? null;
            if ($ta === $tb) return 0;
            if (!$ta) return 1;
            if (!$tb) return -1;
            return strcmp($tb, $ta);
        });

        return response()->json(['ok' => true, 'data' => $items]);
    }

    /** =============================
     *  Open conversation by id
     *  ============================= */
    public function conversation(Conversation $conversation)
    {
        $me = Auth::id();
        abort_unless($me && $conversation->hasUser($me), 403);

        $otherId = $conversation->otherParticipantId($me);
        $u = User::select('id', 'unique_id', 'first_name', 'last_name', 'email', 'created_at', 'country_id', 'last_seen_at')
            ->find($otherId);

        $det     = $this->profileRowForUser($u);
        $avatar  = $this->avatarUrl($u);
        $country = $this->countryName($u?->country_id);

        // average response time (simple conversational delta)
        $avg = $this->avgResponseHuman($conversation, $me, $otherId);

        // messages (last 100)
        $msgs = $conversation->messages()
            ->latest('id')->take(100)->get()
            ->sortBy('id')->values()->map(function ($m) {
                return [
                    'id'         => $m->id,
                    'sender_id'  => $m->sender_id,
                    'body'       => $m->body,
                    'file'       => [
                        'name'     => $m->file_name,
                        'size'     => $m->file_size,
                        'mime'     => $m->file_mime ?? $m->mime_type ?? null,
                        'url'      => $m->publicUrl(),
                        'is_image' => $m->isImage(),
                    ],
                    'status'       => $m->status ?? null,
                    'created_at'   => optional($m->created_at)->toIso8601String(),
                    'seen_at'      => optional($m->seen_at)->toIso8601String(),
                    'delivered_at' => optional($m->delivered_at)->toIso8601String(),
                ];
            });

        // ✅ FIX: online presence check
        $online = $u?->last_seen_at instanceof \Carbon\Carbon
    ? $u->last_seen_at->isAfter(now()->subSeconds(120))
    : false;


        return response()->json([
            'ok' => true,
            'conversation' => [
                'id'           => $conversation->id,
                'from_service' => (bool) (($conversation->meta['from_service'] ?? $conversation->from_service ?? false) ? 1 : 0),
            ],
            'partner' => [
                'id'           => $u?->id,
                'name'         => $this->displayName($u),
                'avatar'       => $avatar,
                'online'       => (bool) $online,
                'last_seen_at' => optional($u?->last_seen_at)->toIso8601String(),
                'avg_response' => $avg,
                'country'      => $country,
                'bio'          => $det->profile_description ?? null,
                'joined_at'    => optional($u?->created_at)->toDateString(),
            ],
            'messages' => $msgs,
        ]);
    }

    /** Mini-chat: get/create conversation + last messages + presence meta (by partner) */
    public function history(Request $request)
    {
        try {
            $me = Auth::id();
            abort_unless($me, 403);

            $validated = $request->validate([
                'partner_id'   => ['required', 'integer', 'exists:users,id'],
                'product_id'   => ['nullable', 'integer', 'exists:products,id'],
                'from_service' => ['nullable', 'boolean'],
                'limit'        => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $partnerId   = (int) $validated['partner_id'];
            if ($partnerId === $me) {
                return response()->json(['ok' => false, 'message' => 'Cannot chat with yourself'], 422);
            }

            $productId   = (int) ($validated['product_id'] ?? 0);
            $fromService = (bool) ($validated['from_service'] ?? false);
            $limit       = (int) ($validated['limit'] ?? 50);

            $conv = Conversation::pair($me, $partnerId);

            if ($fromService) {
                $meta = $conv->meta ?? [];
                $meta['from_service'] = true;
                if ($productId) $meta['origin_product_id'] = $productId;
                $conv->meta = $meta;
                $conv->save();
            }

            $msgs = $conv->messages()->latest('id')->take($limit)->get()->sortBy('id')->values();

            $partner = User::select('id', 'unique_id', 'first_name', 'last_name', 'email', 'country_id', 'last_seen_at')->find($partnerId);
            // ✅ FIX: stable "online" (last_seen < 2m)
            $online  = $partner?->last_seen_at && now()->diffInMinutes($partner->last_seen_at) < 2;
            $avgResp = $this->avgResponseHuman($conv, $me, $partnerId);

            $avatar = $this->avatarUrl($partner);

            return response()->json([
                'ok'              => true,
                'conversation_id' => $conv->id,
                'partner'         => [
                    'id'           => $partnerId,
                    'name'         => $this->displayName($partner),
                    'avatar'       => $avatar,
                    'online'       => (bool) $online,
                    'last_seen_at' => optional($partner?->last_seen_at)->toIso8601String(),
                    'avg_response' => $avgResp,
                ],
                'messages' => $msgs->map(function ($m) {
                    return [
                        'id'        => $m->id,
                        'sender_id' => $m->sender_id,
                        'body'      => $m->body,
                        'file'      => [
                            'name'     => $m->file_name,
                            'size'     => $m->file_size,
                            'mime'     => $m->file_mime ?? $m->mime_type ?? null,
                            'url'      => $m->publicUrl(),
                            'is_image' => $m->isImage(),
                        ],
                        'status'     => $m->status ?? null,
                        'created_at' => optional($m->created_at)->toIso8601String(),
                    ];
                }),
            ]);
        } catch (\Throwable $e) {
            // ✅ FIX: never leak HTML; always JSON
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Create/start conversation (by partner id) */
    public function open(Request $request)
    {
        try {
            $request->validate([
                'partner_id'   => ['required', 'integer', 'exists:users,id'],
                'product_id'   => ['nullable', 'integer', 'exists:products,id'],
                'from_service' => ['nullable', 'boolean'],
            ]);

            $me = Auth::id();
            abort_unless($me, 403);

            $partnerId = (int) $request->partner_id;
            if ($me === $partnerId) {
                return response()->json(['ok' => false, 'message' => 'Cannot chat with yourself'], 422);
            }

            $conv = Conversation::pair($me, $partnerId);
            $meta = $conv->meta ?? [];
            if ($request->boolean('from_service')) $meta['from_service'] = true;
            if ($request->filled('product_id')) $meta['origin_product_id'] = (int) $request->product_id;
            $conv->meta = $meta;
            $conv->save();

            return response()->json(['ok' => true, 'conversation_id' => $conv->id]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Product page mini-send */
    public function seed(Request $request)
    {
        try {
            $me = Auth::id();
            abort_unless($me, 403);

            $validated = $request->validate([
                'partner_id'   => ['required', 'integer', 'exists:users,id'],
                'product_id'   => ['nullable', 'integer', 'exists:products,id'],
                'from_service' => ['nullable', 'boolean'],
                'body'         => ['nullable', 'string', 'max:10000', function ($attr, $value, $fail) {
                    if (!$value) return;
                    if (preg_match('/\b[\w\.-]+@[\w\.-]+\.\w{2,}\b/i', $value)) $fail('Email not allowed.');
                    if (preg_match('/\+?\d[\d\-\s()]{7,}\d/', $value)) $fail('Phone number not allowed.');
                }],
                'file'         => ['nullable', 'file', 'max:5120000'],
            ]);

            $partnerId = (int) $validated['partner_id'];
            if ($partnerId === $me) {
                return response()->json(['ok' => false, 'message' => 'Cannot chat with yourself'], 422);
            }

            $conv = Conversation::pair($me, $partnerId);

            $meta = $conv->meta ?? [];
            if ($request->boolean('from_service')) $meta['from_service'] = true;
            if ($request->filled('product_id')) $meta['origin_product_id'] = (int) $request->product_id;
            $conv->meta = $meta;
            $conv->save();

            $filePath = $fileName = $fileMime = null;
            $fileSize = null;

            if ($request->hasFile('file')) {
                $f = $request->file('file');
                $filePath = $f->store('chat/' . $conv->id, 'public');
                $fileName = $f->getClientOriginalName();
                $fileMime = $f->getClientMimeType();
                $fileSize = $f->getSize();
            }

            $body = trim((string) $request->input('body', ''));

            if ($body === '' && !$filePath) {
                return response()->json(['ok' => false, 'message' => 'Empty message'], 422);
            }

            $columns = [
                'conversation_id' => $conv->id,
                'sender_id'       => $me,
                'body'            => $body ?: null,
                'file_path'       => $filePath,
                'file_name'       => $fileName,
                'file_size'       => $fileSize,
            ];

            if (Schema::hasColumn('messages', 'file_mime')) {
                $columns['file_mime'] = $fileMime;
            } elseif (Schema::hasColumn('messages', 'mime_type')) {
                $columns['mime_type'] = $fileMime;
            }

            $msg = Message::create($columns);

            if (Schema::hasColumn('conversations', 'last_message_id')) {
                $conv->last_message_id = $msg->id;
                $conv->save();
            }

            broadcast(new NewMessage($msg))->toOthers();

            return response()->json([
                'ok'              => true,
                'conversation_id' => $conv->id,
                'message'         => [
                    'id'   => $msg->id,
                    'body' => $msg->body,
                    'file' => [
                        'name'     => $msg->file_name,
                        'size'     => $msg->file_size,
                        'mime'     => $msg->file_mime ?? $msg->mime_type ?? null,
                        'url'      => $msg->publicUrl(),
                        'is_image' => $msg->isImage(),
                    ],
                    'created_at' => optional($msg->created_at)->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Full chat send */
    public function send(Conversation $conversation, Request $request)
    {
        try {
            $me = Auth::id();
            $other = $conversation->otherParticipantId($me);
            if ($other === null) {
                return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'body' => ['nullable', 'string', 'max:10000'],
                'file' => ['nullable', 'file', 'max:5120000'],
            ]);

            $body = trim((string) $request->input('body', ''));
            if ($body) {
                // ✅ FIX: explicit anti-contact sharing
                if (preg_match('/\b[\w\.-]+@[\w\.-]+\.\w{2,}\b/i', $body) || preg_match('/\+?\d[\d\-\s()]{7,}\d/', $body)) {
                    return response()->json(['ok' => false, 'message' => 'Sharing email or phone is not allowed.'], 422);
                }
            }

            $filePath = $fileName = $fileMime = null;
            $fileSize = null;

            if ($request->hasFile('file')) {
                $f = $request->file('file');
                $filePath = $f->store('chat/' . $conversation->id, 'public');
                $fileName = $f->getClientOriginalName();
                $fileMime = $f->getClientMimeType();
                $fileSize = $f->getSize();
            }

            if ($body === '' && !$filePath) {
                return response()->json(['ok' => false, 'message' => 'Empty message'], 422);
            }

            $data = [
                'conversation_id' => $conversation->id,
                'sender_id'       => $me,
                'body'            => $body ?: null,
                'file_path'       => $filePath,
                'file_name'       => $fileName,
                'file_size'       => $fileSize,
            ];
            if (Schema::hasColumn('messages', 'file_mime')) {
                $data['file_mime'] = $fileMime;
            } elseif (Schema::hasColumn('messages', 'mime_type')) {
                $data['mime_type'] = $fileMime;
            }

            $msg = Message::create($data);

            if (Schema::hasColumn('conversations', 'last_message_id')) {
                $conversation->last_message_id = $msg->id;
                $conversation->save();
            }

            broadcast(new NewMessage($msg))->toOthers();

            return response()->json([
                'ok'      => true,
                'message' => [
                    'id'   => $msg->id,
                    'body' => $msg->body,
                    'file' => [
                        'name'     => $msg->file_name,
                        'size'     => $msg->file_size,
                        'mime'     => $msg->file_mime ?? $msg->mime_type ?? null,
                        'url'      => $msg->publicUrl(),
                        'is_image' => $msg->isImage(),
                    ],
                    'created_at' => optional($msg->created_at)->toIso8601String(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            // ✅ FIX: swallow framework HTML and return JSON
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Delivered */
    public function markDelivered(Request $request, Conversation $conversation)
    {
        try {
            $me = Auth::id();
            abort_unless($me && $conversation->hasUser($me), 403);

            $changed = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $me)
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now(), 'status' => 'delivered']);

            if ($changed) {
                // Broadcast the highest delivered id so UI can mark all up to it
                $last = \App\Models\Message::where('conversation_id', $conversation->id)
                    ->where('sender_id', '!=', $me)
                    ->max('id');
                if ($last) {
                    broadcast(new \App\Events\MessageDelivered($conversation->id, $last))->toOthers();
                }
            }
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Seen */
    public function markSeen(Request $request, Conversation $conversation)
    {
        try {
            $me = Auth::id();
            abort_unless($me && $conversation->hasUser($me), 403);

            $last = Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $me)->latest('id')->first();

            if ($last) {
                // ✅ FIX: ensure delivered_at is set when seen
                Message::where('conversation_id', $conversation->id)
                    ->where('sender_id', '!=', $me)
                    ->where(function ($q) {
                        $q->whereNull('seen_at')->orWhereNull('delivered_at');
                    })
                    ->update(['seen_at' => now(), 'delivered_at' => now(), 'status' => 'seen']);

                MessageRead::updateOrCreate(
                    ['conversation_id' => $conversation->id, 'user_id' => $me],
                    ['last_read_message_id' => $last->id, 'last_read_at' => now()]
                );

                broadcast(new MessageSeen($conversation->id, $last->id))->toOthers();
            }
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Typing */
    public function typing(Request $request, Conversation $conversation)
    {
        try {
            $me = Auth::id();
            abort_unless($me && $conversation->hasUser($me), 403);
            $request->validate(['typing' => ['required', 'boolean']]);

            // ✅ FIX: lightweight payload, broadcast to presence room
            broadcast(new UserTyping($conversation->id, $me, $request->boolean('typing')))->toOthers();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Details pane (right column on full page) */
    public function details(Conversation $conversation)
    {
        $me = Auth::id();
        abort_unless($me && $conversation->hasUser($me), 403);

        $otherId = $conversation->otherParticipantId($me);
        $u = User::select('id', 'unique_id', 'first_name', 'last_name', 'email', 'created_at', 'country_id', 'last_seen_at')
            ->find($otherId);

        $det     = $this->profileRowForUser($u);
        $avatar  = $this->avatarUrl($u);
        // ✅ FIX: same online calc everywhere
        $online = $u?->last_seen_at instanceof \Carbon\Carbon
    ? $u->last_seen_at->isAfter(now()->subSeconds(120))
    : false;


        return response()->json([
            'ok' => true,
            'partner' => [
                'id'           => $u?->id,
                'name'         => $this->displayName($u),
                'avatar'       => $avatar,
                'online'       => (bool) $online,
                'last_seen_at' => optional($u?->last_seen_at)->toIso8601String(),
                'joined_at'    => optional($u?->created_at)->toDateString(),
                'country'      => $this->countryName($u?->country_id),
                'bio'          => $det->profile_description ?? null,
            ],
        ]);
    }

    /** Load message history by conversation id (full chat page) */
    public function historyById(Conversation $conversation, Request $request)
    {
        try {
            $me = Auth::id();
            abort_unless($me && $conversation->hasUser($me), 403);

            $limit = max(1, min((int) $request->query('limit', 100), 200));

            $msgs = $conversation->messages()
                ->latest('id')->take($limit)->get()
                ->sortBy('id')->values()->map(function ($m) {
                    return [
                        'id'        => $m->id,
                        'sender_id' => $m->sender_id,
                        'body'      => $m->body,
                        'file'      => [
                            'name'     => $m->file_name,
                            'size'     => $m->file_size,
                            'mime'     => $m->file_mime ?? $m->mime_type ?? null,
                            'url'      => $m->publicUrl(),
                            'is_image' => $m->isImage(),
                        ],
                        'status'       => $m->status ?? null,
                        'created_at'   => optional($m->created_at)->toIso8601String(),
                        'seen_at'      => optional($m->seen_at)->toIso8601String(),
                        'delivered_at' => optional($m->delivered_at)->toIso8601String(),
                    ];
                });

            return response()->json(['ok' => true, 'messages' => $msgs]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Util: avg response time in human form */
private function avgResponseHuman(Conversation $conv, int $me, int $partnerId): string
{
    $msgs = $conv->messages()
        ->select('id', 'sender_id', 'created_at')
        ->latest('id')->take(200)->get()
        ->sortBy('id')->values();

    if ($msgs->isEmpty()) return '—';

    $deltas = [];
    for ($i = 0; $i < count($msgs) - 1; $i++) {
        $m1 = $msgs[$i];
        $m2 = $msgs[$i + 1];

        // Only measure partner's response time to my message
        if ((int) $m1->sender_id === $me && (int) $m2->sender_id === $partnerId) {
            if ($m2->created_at && $m1->created_at && $m2->created_at->gte($m1->created_at)) {
                $deltas[] = $m2->created_at->diffInSeconds($m1->created_at);
            }
        }
    }

    if (empty($deltas)) return '—';

    $avg = max(0, (int) round(array_sum($deltas) / max(1, count($deltas))));

    // Convert into human friendly units
    if ($avg < 60) {
        $val = $avg;
        $unit = $val === 1 ? 'second' : 'seconds';
    } elseif ($avg < 3600) {
        $val = round($avg / 60);
        $unit = $val === 1 ? 'minute' : 'minutes';
    } elseif ($avg < 86400) {
        $val = round($avg / 3600);
        $unit = $val === 1 ? 'hour' : 'hours';
    } elseif ($avg < 2592000) { // ~30 days
        $val = round($avg / 86400);
        $unit = $val === 1 ? 'day' : 'days';
    } elseif ($avg < 31536000) { // ~12 months
        $val = round($avg / 2592000);
        $unit = $val === 1 ? 'month' : 'months';
    } else {
        $val = round($avg / 31536000, 1);
        $unit = $val == 1 ? 'year' : 'years';
    }

    return "≈ {$val} {$unit}";
}



}
