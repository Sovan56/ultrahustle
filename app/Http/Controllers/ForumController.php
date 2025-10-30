<?php

namespace App\Http\Controllers;

use App\Models\Forum\ForumCommentLike;
use App\Models\Forum\ForumFollow;
use App\Models\Forum\ForumThreadLike;
use App\Models\Forum\ForumThreadSave;
use App\Models\Forum\ForumThreadShare;
use App\Models\Forum\ForumReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Models\Forum\{ForumThread, ForumCategory, ForumPoll, ForumPollOption, ForumComment, ForumPollVote};


class ForumController extends Controller
{
    /* ======================= Public Pages ======================= */

    // List page (Blade)
    public function index()
    {
        return view('forum');
    }

    // Show page (Blade)
    public function show(ForumThread $thread)
    {
        return view('forum_show', [
            'threadId' => $thread->id,
        ]);
    }

    /* ======================= JSON APIs for Forum List ======================= */

    // Infinite scroll list API
    public function list(Request $req)
    {
        $per       = (int) $req->integer('per', 5);
        $category  = trim((string)$req->query('category', ''));
        $q         = trim((string)$req->query('q', ''));

        $query = ForumThread::withBasics()->latest('id');

        // Saved-only filter
if ($req->boolean('saved')) {
    if (!auth()->check()) {
        return response()->json(['data'=>[], 'next'=>null]); // guests have no saved list
    }
    $ids = ForumThreadSave::where('user_id', auth()->id())->pluck('thread_id');
    $query->whereIn('id', $ids);
}


        if ($category && strtolower($category) !== 'all') {
            $cat = ForumCategory::where('name', $category)
                    ->orWhere('slug', str($category)->slug('-'))
                    ->first();
            if ($cat) $query->where('category_id', $cat->id);
            else $query->whereRaw('1=0'); // no results
        }

        if ($q) {
            $query->where(function($w) use ($q) {
                $w->where('title', 'like', "%$q%")
                  ->orWhere('excerpt', 'like', "%$q%")
                  ->orWhere('body_html', 'like', "%$q%");
            });
        }

        $paginator = $query->paginate($per)->appends($req->query());

        $userId = auth()->id();
        $data = $paginator->getCollection()->map(function(ForumThread $t) use ($userId) {
            return $this->threadToCard($t, $userId);
        });

        return response()->json([
            'data' => $data,
            'next' => $paginator->hasMorePages() ? $paginator->nextPageUrl() : null,
        ]);
    }

    /* ======================= Create Thread ======================= */

    public function store(Request $req)
    {
        // Only allow authenticated users
        if (!auth()->check()) {
            return response()->json(['ok'=>false, 'message'=>'Login required'], 401);
        }

        $postType = $req->input('post_type', 'text');

        $rules = [
            'title'       => ['required','string','max:255'],
            'category'    => ['nullable','string','max:100'],
            'post_type'   => ['required', Rule::in(['text','image','video','poll'])],
            'body_html'   => ['nullable','string'],
            'media_url'   => ['nullable','string','max:1000'],
            'media_poster'=> ['nullable','string','max:1000'],
            'media_alt'   => ['nullable','string','max:255'],
        ];

        if ($postType === 'poll') {
            $rules['poll_options']  = ['required','array','min:2','max:12'];
            $rules['poll_options.*']= ['required','string','max:200'];
            $rules['poll_multiple'] = ['nullable','boolean'];
        }

        $v = $req->validate($rules);

        // Resolve/create category
        $categoryId = null;
        if (!empty($v['category'])) {
            $slug = str($v['category'])->slug('-');
            $cat = ForumCategory::firstOrCreate(
                ['slug'=>$slug],
                ['name'=>$v['category']]
            );
            $categoryId = $cat->id;
        }

        $thread = ForumThread::create([
            'user_id'     => auth()->id(),
            'category_id' => $categoryId,
            'title'       => $v['title'],
            'post_type'   => $postType,
            'excerpt'     => $this->makeExcerpt($v['body_html'] ?? null),
            'body_html'   => $v['body_html'] ?? null,
            'media_url'   => $v['media_url'] ?? null,
            'media_poster'=> $v['media_poster'] ?? null,
            'media_alt'   => $v['media_alt'] ?? null,
        ]);

        // Create poll if requested
        if ($postType === 'poll') {
            $poll = ForumPoll::create([
                'thread_id' => $thread->id,
                'multiple'  => (bool) ($v['poll_multiple'] ?? false),
            ]);
            $pos = 1;
            foreach ($v['poll_options'] as $label) {
                ForumPollOption::create([
                    'poll_id'  => $poll->id,
                    'label'    => $label,
                    'position' => $pos++,
                ]);
            }
        }

        return response()->json(['ok'=>true, 'id'=>$thread->id]);
    }

    /* ======================= Show Thread Data (post + comments) ======================= */

public function showData(Request $req, ForumThread $thread)
{
    $thread->load(['user.anotherDetail', 'category', 'poll.options']);
    $userId = auth()->id();

    $post = $this->threadToPost($thread, $userId);

    // Ensure author meta exists consistently for the frontend
    $post['author'] = $post['author'] ?? [];
    $post['author']['id'] = $thread->user_id;
    $post['author']['name'] = $post['author']['name'] ?? (
        trim($thread->user->name ?? '') ?: ($thread->user->email ?? 'User')
    );
    $post['author']['avatar'] = $post['author']['avatar'] ?? $this->userAvatarUrl($thread->user);

    // Follow state (Follow / Followed button)
    $authorFollowed = false;
    if ($userId && $thread->user_id !== $userId) {
        $authorFollowed = \App\Models\Forum\ForumFollow::where('follower_id', $userId)
            ->where('followed_id', $thread->user_id)
            ->exists();
    }
    $post['author_followed'] = $authorFollowed;

    // First page of comments (top-level); replies are nested by commentsTree()
    $per = (int) $req->integer('per', 10);
    $commentsPaginator = $this->commentsPaginator($thread->id, null, $per);
    $comments = $this->commentsTree($commentsPaginator->getCollection(), $userId);

    // Trending: robust against missing columns (no 'is_published' assumption)
    try {
        $trendingQ = \App\Models\Forum\ForumThread::query()
            ->select('id', 'title')
            ->orderByDesc('likes_count'); // relies on your counter column

        // If you want a recency window, uncomment the next line.
        // $trendingQ->where('created_at', '>=', now()->subDays(30));

        $trending = $trendingQ->limit(5)->get()
            ->map(fn($t) => ['id' => $t->id, 'title' => $t->title]);
    } catch (\Throwable $e) {
        // Fallback if likes_count or anything else is missing
        $trending = collect();
    }

    return response()->json([
        'post'           => $post,
        'comments'       => $comments,
        'comments_next'  => $commentsPaginator->hasMorePages() ? $commentsPaginator->nextPageUrl() : null,
        'trending'       => $trending,
    ]);
}


    /* ======================= Likes / Saves / Shares ======================= */

 public function like(ForumThread $thread)
{
    if (!auth()->check()) {
        return response()->json(['message' => 'Login required'], 401);
    }

    $like = ForumThreadLike::where('thread_id', $thread->id)
        ->where('user_id', auth()->id())
        ->first();

    if ($like) {
        $like->delete();
        $thread->decrement('likes_count');
        $liked = false;
    } else {
        ForumThreadLike::create([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
        ]);
        $thread->increment('likes_count');
        $liked = true;
    }

    $thread->refresh();

    return response()->json([
        'liked' => $liked,
        'count' => $thread->likes_count,
    ]);
}




    public function save(ForumThread $thread)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['message' => 'Login required'], 401);
    }

    $existing = ForumThreadSave::where('thread_id', $thread->id)
        ->where('user_id', $user->id)
        ->first();

    if ($existing) {
        $existing->delete();
        $thread->decrement('saves_count');
        $saved = false;
    } else {
        ForumThreadSave::create([
            'thread_id' => $thread->id,
            'user_id'   => $user->id,
        ]);
        $thread->increment('saves_count');
        $saved = true;
    }

    return response()->json([
        'saved' => $saved,
        'count' => $thread->saves_count
    ]);
}


    public function share(Request $req, ForumThread $thread)
    {
        $channel = $req->string('channel')->toString() ?: null;
        ForumThreadShare::create([
            'thread_id' => $thread->id,
            'user_id'   => auth()->id(),
            'channel'   => $channel,
        ]);
        $thread->increment('shares_count');
        return response()->json(['ok'=>true,'count'=>$thread->shares_count]);
    }

    /* ======================= Follow Author ======================= */

    public function follow(User $user)
    {
        if (!auth()->check()) return response()->json(['message'=>'Login required'], 401);
        if ($user->id === auth()->id()) return response()->json(['message'=>'Cannot follow yourself'], 422);

        $row = ForumFollow::where('follower_id', auth()->id())->where('followed_id',$user->id)->first();
        if ($row) {
            $row->delete();
            $followed = false;
        } else {
            ForumFollow::create(['follower_id'=>auth()->id(),'followed_id'=>$user->id]);
            $followed = true;
        }
        return response()->json(['followed'=>$followed]);
    }

    /* ======================= Report ======================= */

    public function report(Request $req, ForumThread $thread)
    {
        if (!auth()->check()) return response()->json(['message'=>'Login required'], 401);

        $v = $req->validate([
            'reason' => ['nullable','string','max:255'],
            'notes'  => ['nullable','string','max:2000'],
        ]);

        ForumReport::create([
            'thread_id' => $thread->id,
            'comment_id'=> null,
            'user_id'   => auth()->id(),
            'reason'    => $v['reason'] ?? null,
            'notes'     => $v['notes'] ?? null,
        ]);

        return response()->json(['ok'=>true]);
    }

    /* ======================= Poll Vote ======================= */

    public function vote(Request $req, ForumPoll $poll)
    {
        if (!auth()->check()) return response()->json(['message'=>'Login required'], 401);

        $v = $req->validate([
            'option_ids' => ['required','array','min:1'],
            'option_ids.*' => ['integer','exists:forum_poll_options,id'],
        ]);

        $userId = auth()->id();

        // If multiple=false, ensure only 1 option
        if (!$poll->multiple && count($v['option_ids']) > 1) {
            return response()->json(['ok'=>false,'message'=>'This poll allows only one vote'], 422);
        }

        // If already voted and poll is single-choice, block; if multiple, allow per-option unique
        if (!$poll->multiple && $poll->votes()->where('user_id',$userId)->exists()) {
            return response()->json(['ok'=>false,'message'=>'You already voted'], 422);
        }

        // Save votes
        $added = 0;
        foreach ($v['option_ids'] as $optId) {
            $opt = ForumPollOption::where('id',$optId)->where('poll_id',$poll->id)->first();
            if (!$opt) continue;

            $exists = ForumPollVote::where([
                'poll_id'  => $poll->id,
                'option_id'=> $opt->id,
                'user_id'  => $userId,
            ])->exists();

            if (!$exists) {
                ForumPollVote::create([
                    'poll_id'  => $poll->id,
                    'option_id'=> $opt->id,
                    'user_id'  => $userId,
                ]);
                $opt->increment('votes');
                $added++;
            }
        }

        if ($added > 0) {
            $poll->increment('total_votes', $added);
        }

        // Return updated options
        $poll->load('options');
        return response()->json([
            'ok' => true,
            'total' => $poll->total_votes,
            'options' => $poll->options->map(fn($o)=>[
                'id'    => $o->id,
                'label' => $o->label,
                'votes' => (int)$o->votes,
            ]),
        ]);
    }

    /* ======================= Comments ======================= */

    public function comments(Request $req, ForumThread $thread)
    {
        $per = (int)$req->integer('per', 10);
        $parentId = $req->has('parent_id') ? (int)$req->integer('parent_id') : null;

        $paginator = $this->commentsPaginator($thread->id, $parentId, $per);
        $userId = auth()->id();

        return response()->json([
            'data' => $this->commentsTree($paginator->getCollection(), $userId),
            'next' => $paginator->hasMorePages() ? $paginator->nextPageUrl() : null,
        ]);
    }

    public function commentStore(Request $req, ForumThread $thread)
    {
        if (!auth()->check()) return response()->json(['message'=>'Login required'], 401);

        $v = $req->validate([
            'parent_id' => ['nullable','integer','exists:forum_comments,id'],
            'body_html' => ['required','string','max:20000'],
        ]);

        $comment = ForumComment::create([
            'thread_id' => $thread->id,
            'user_id'   => auth()->id(),
            'parent_id' => $v['parent_id'] ?? null,
            'body_html' => $v['body_html'],
        ]);

        $thread->increment('comments_count');

        return response()->json(['ok'=>true,'id'=>$comment->id]);
    }

    public function commentLike(ForumComment $comment)
    {
        if (!auth()->check()) return response()->json(['message'=>'Login required'], 401);

        $row = ForumCommentLike::where('comment_id',$comment->id)->where('user_id',auth()->id())->first();
        if ($row) {
            $row->delete();
            $comment->decrement('likes_count');
            $liked = false;
        } else {
            ForumCommentLike::create(['comment_id'=>$comment->id,'user_id'=>auth()->id()]);
            $comment->increment('likes_count');
            $liked = true;
        }
        $comment->refresh();
        return response()->json(['liked'=>$liked,'count'=>$comment->likes_count]);
    }

    /* ======================= Upload (images for comments if needed) ======================= */


public function upload(Request $req)
{
    if (!auth()->check()) {
        return response()->json(['message' => 'Login required'], 401);
    }

    // Same config as before
    $maxImageMB    = 10;
    $maxVideoMB    = 200;
    $allowedImages = ['image/jpeg','image/png','image/gif','image/webp'];
    $allowedVideos = ['video/mp4','video/webm','video/ogg','video/quicktime'];

    $req->validate(['file' => ['required','file']], [
        'file.required' => 'Please choose a file to upload.',
        'file.file'     => 'The upload looks invalid. Try again.',
    ]);

    $file = $req->file('file');
    $mime = $file->getMimeType() ?? '';
    $isVideo = str_starts_with($mime, 'video/');
    $allowedMimes = $isVideo ? $allowedVideos : $allowedImages;
    $maxMB = $isVideo ? $maxVideoMB : $maxImageMB;

    $rules = [
        'file' => [
            'file',
            'max:' . ($maxMB * 1024),
            'mimetypes:' . implode(',', $allowedMimes),
        ],
    ];

    $typesNice = implode(', ', array_map(fn($m) => preg_replace('#.*/#', '', $m), $allowedMimes));
    $messages = [
        'file.max' => 'File is too large. Max allowed is ' . $maxMB . ' MB. Your file is ~' . round(($file->getSize() ?? 0) / 1024 / 1024, 1) . ' MB.',
        'file.mimetypes' => 'Unsupported file type. Allowed ' . ($isVideo ? 'videos' : 'images') . ': ' . $typesNice . '.',
    ];

    try {
        validator($req->all(), $rules, $messages)->validate();
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }

    $dir = $isVideo ? 'forum/videos' : 'forum/uploads';
    $path = $file->store($dir, 'public');
    $url  = route('media.pass', ['path' => $path]);

    return response()->json([
        'url'  => $url,
        'type' => $isVideo ? 'video' : 'image',
    ]);
}




public function categories()
{
    $cats = \App\Models\Forum\ForumCategory::orderBy('name')->get(['id','name','slug','color_bg','color_fg','color_border']);
    return response()->json([
        'data' => $cats->map(function($c){
            return [
                'id'     => $c->id,
                'name'   => $c->name,
                'slug'   => $c->slug,
                'colors' => [
                    'bg'     => $c->color_bg,
                    'fg'     => $c->color_fg,
                    'border' => $c->color_border,
                ],
            ];
        }),
    ]);
}



    /* ======================= Admin (under checkUser group as requested) ======================= */

public function adminIndex(Request $req)
{
    $qStr = trim((string)$req->query('q', ''));

    // Only the logged-in user's threads
    $q = ForumThread::with(['category:id,name', 'user:id,first_name,last_name,email'])
        ->withCount(['likes','comments','shares'])
        ->where('user_id', auth()->id())
        ->latest();

    if ($qStr !== '') {
        $q->where('title', 'like', "%{$qStr}%");
    }

    // No pagination: load all of THIS user's threads
    $threads = $q->get();

    return view('UserAdmin.forum.index', [
        'threads' => $threads,
        'q'       => $qStr,
    ]);
}

public function adminEdit(ForumThread $thread)
{
    // Owner gate
    if ($thread->user_id !== auth()->id()) abort(403);

    $thread->load(['category','poll.options']);
    $categories = ForumCategory::orderBy('name')->get(['id','name']);
    $postType = $thread->post_type;

    return view('UserAdmin.forum.edit', compact('thread','categories','postType'));
}

public function adminUpdate(Request $req, ForumThread $thread)
{
    if ($thread->user_id !== auth()->id()) abort(403);

    $v = $req->validate([
        'title'       => ['required','string','max:255'],
        'category_id' => ['nullable','integer','exists:forum_categories,id'],

        'body_html'   => ['nullable','string'],
        'media_url'    => ['nullable','string','max:1000'],
        'media_poster' => ['nullable','string','max:1000'],
        'media_alt'    => ['nullable','string','max:255'],

        'poll_multiple'  => ['nullable','boolean'],
        'poll_options'   => ['nullable','array','min:2','max:12'],
        'poll_options.*' => ['nullable','string','max:200'],
    ]);

    $data = [
        'title'       => $v['title'],
        'category_id' => $v['category_id'] ?? $thread->category_id,
    ];

    switch ($thread->post_type) {
        case 'text':
            $data['body_html'] = $v['body_html'] ?? $thread->body_html;
            $data['excerpt']   = $this->makeExcerpt($data['body_html']);
            break;

        case 'image':
            $data['media_url']  = $v['media_url'] ?? $thread->media_url;
            $data['media_alt']  = $v['media_alt'] ?? $thread->media_alt;
            $data['body_html']  = $v['body_html'] ?? $thread->body_html; // caption
            $data['excerpt']    = $this->makeExcerpt($data['body_html']);
            $data['media_poster'] = null;
            break;

        case 'video':
            $data['media_url']    = $v['media_url'] ?? $thread->media_url;
            $data['media_poster'] = $v['media_poster'] ?? $thread->media_poster;
            $data['media_alt']    = $v['media_alt'] ?? $thread->media_alt;
            $data['body_html']    = $v['body_html'] ?? $thread->body_html; // description
            $data['excerpt']      = $this->makeExcerpt($data['body_html']);
            break;

        case 'poll':
            $data['body_html'] = $v['body_html'] ?? $thread->body_html;
            $data['excerpt']   = $this->makeExcerpt($data['body_html']);
            break;
    }

    $thread->update($data);

    if ($thread->post_type === 'poll') {
        $thread->load('poll.options');
        $poll = $thread->poll ?: ForumPoll::create([
            'thread_id' => $thread->id,
            'multiple'  => (bool)($v['poll_multiple'] ?? false),
        ]);
        $poll->update(['multiple' => (bool)($v['poll_multiple'] ?? false)]);

        $incoming = collect($v['poll_options'] ?? [])
            ->filter(fn($t)=>$t !== null && trim($t) !== '')
            ->values();

        $poll->options()->delete();
        $pos = 1;
        foreach ($incoming as $label) {
            ForumPollOption::create([
                'poll_id'  => $poll->id,
                'label'    => $label,
                'position' => $pos++,
            ]);
        }
    }

    return redirect()->route('admin.forum.page')->with('status', 'Thread updated');
}

public function adminDestroy(ForumThread $thread)
{
    if ($thread->user_id !== auth()->id()) abort(403);

    ForumThreadLike::where('thread_id',$thread->id)->delete();
    ForumThreadSave::where('thread_id',$thread->id)->delete();
    ForumThreadShare::where('thread_id',$thread->id)->delete();
    ForumComment::where('thread_id',$thread->id)->delete();

    ForumPoll::where('thread_id', $thread->id)->each(function($p){
        ForumPollVote::where('poll_id',$p->id)->delete();
        ForumPollOption::where('poll_id',$p->id)->delete();
        $p->delete();
    });

    $thread->delete();
    return back()->with('status','Deleted');
}


    /* ======================= Helpers (transformers + tree) ======================= */

    private function userAvatarUrl(?User $u): ?string
    {
        if (!$u) return null;
        $pp = $u->anotherDetail->profile_picture ?? null;
        if (!$pp) return null;
        // ensure compatible with /media proxy (you added)
        return route('media.pass', ['path' => $pp]);
    }

    private function timeAgoShort($ts): string
    {
        // Laravel 10: diffForHumans(short: true)
        return optional($ts)->diffForHumans(['parts'=>2, 'short'=>true]) ?? '';
    }

    private function threadToCard(ForumThread $t, ?int $userId): array
    {
        $authorName = trim($t->user->name ?? '') ?: ($t->user->email ?? 'User');
        $timeAgo = $this->timeAgoShort($t->created_at); // like "2h"
        return [
            'id'           => $t->id,
            'title'        => $t->title,
            'content'      => $t->excerpt,
            'author'       => $authorName,
            'author_avatar'=> $this->userAvatarUrl($t->user),
            'timeAgo'      => $timeAgo,
            'category'     => $t->category?->name ?? 'General',
            'likes'        => (int)$t->likes_count,
            'commentCount' => (int)$t->comments_count,
            'postType'     => $t->post_type,
            'mediaUrl'     => $t->media_url,
            'mediaPoster'  => $t->media_poster,
            'mediaAlt'     => $t->media_alt,
            'liked'        => $userId ? $t->isLikedBy($userId) : false,
            'saved'        => $userId ? $t->isSavedBy($userId) : false,
        ];
    }

    private function threadToPost(ForumThread $t, ?int $userId): array
    {
        $authorName = trim($t->user->name ?? '') ?: ($t->user->email ?? 'User');
        $community  = $t->category?->name ?? 'General';

        $poll = null;
        if ($t->post_type === 'poll' && $t->poll) {
            $poll = [
                'id'       => $t->poll->id,
                'multiple' => (bool)$t->poll->multiple,
                'total'    => (int)$t->poll->total_votes,
                'options'  => $t->poll->options->map(fn($o)=>[
                    'id'    => $o->id,
                    'label' => $o->label,
                    'votes' => (int)$o->votes,
                ])->values(),
                'voted'    => $userId ? $t->poll->hasVoted($userId) : false,
            ];
        }

        return [
            'id'        => $t->id,
            'community' => $community,
            'title'     => $t->title,
            'author'    => [
                'id'     => $t->user->id,
                'name'   => $authorName,
                'avatar' => $this->userAvatarUrl($t->user),
            ],
            'time'      => $t->created_at?->diffForHumans() ?? '',
            'tags'      => [], // optional
            'media'     => [
                'type'  => $t->post_type,
                'url'   => $t->media_url,
                'poster'=> $t->media_poster,
            ],
            'bodyHtml'  => $t->body_html,
            'cta'       => null,
            'upvotes'   => (int)$t->likes_count,
            'comments'  => (int)$t->comments_count,
            'liked'     => $userId ? $t->isLikedBy($userId) : false,
            'saved'     => $userId ? $t->isSavedBy($userId) : false,
            'poll'      => $poll,
        ];
    }

private function commentsPaginator(int $threadId, ?int $parentId, int $per)
{
    $q = ForumComment::with(['user.anotherDetail'])
          ->withCount('replies') // << add
          ->where('thread_id',$threadId)
          ->when($parentId, fn($w)=>$w->where('parent_id',$parentId), fn($w)=>$w->whereNull('parent_id'))
          ->orderBy('id','desc');

    return $q->paginate($per);
}


private function commentsTree($comments, ?int $userId)
{
    return $comments->map(function(ForumComment $c) use ($userId) {
        // compute once to avoid N+1 heavy loads:
        $hasChildren = $c->replies()->exists();

        return [
            'id'       => $c->id,
            'author'   => trim($c->user->name ?? '') ?: ($c->user->email ?? 'User'),
            'avatar'   => $this->userAvatarUrl($c->user),
            'time'     => $this->timeAgoShort($c->created_at),
            'content'  => $c->body_html,
            'reactions'=> [
                'up'     => (int)$c->likes_count,
                'down'   => 0,
                'emojis' => new \stdClass(),
            ],
            // IMPORTANT: do not preload replies here
            'replies'       => [],
            'has_children'  => $hasChildren,
        ];
    })->values();
}




    private function makeExcerpt(?string $html): ?string
    {
        if (!$html) return null;
        $text = trim(strip_tags($html));
        if ($text === '') return null;
        return str($text)->limit(220)->toString();
    }















    
}
