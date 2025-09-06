<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserAdminTeam;
use App\Models\TeamMember;
use App\Models\TeamProject;
use App\Models\TeamProjectImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\Facade\DB;
use App\Models\SitePage;


class AdminController extends Controller
{
    /* ===================== Helpers ===================== */

    // Build public URL through your /media passthrough (public disk)
    private function mediaUrl(?string $path): ?string
    {
        if (!$path) return null;
        $path = ltrim($path, '/');
        return url('media/' . $path);
    }

    private function defaultAvatar(): string
    {
        return asset('assets/img/default-user.png');
    }

    // Return a safe image URL (used by views/JS via onerror too)
    private function safeImg(?string $path, bool $forAvatar = false): string
    {
        $url = $this->mediaUrl($path);
        if (!$url && $forAvatar) {
            return $this->defaultAvatar();
        }
        return $url ?: '';
    }

    /* ===================== AUTH ===================== */

    public function showLogin(Request $request)
    {
        if ($request->session()->has('admin_id')) {
            return redirect()->route('admin.members.page'); // go to all members
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:4'],
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $admin = Admin::where('email', $request->email)->first();
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['ok' => false, 'message' => 'Invalid credentials'], 422);
        }
        if ($admin->status !== 'active') {
            return response()->json(['ok' => false, 'message' => 'Account disabled'], 403);
        }

        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_name', $admin->name);
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'redirect' => route('admin.members.page')]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id', 'admin_name']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    /* ===================== PAGES ===================== */

    public function membersPage()
    {
        return view('admin.members');
    }

    public function teamsPage()
    {
        return view('admin.teams');
    }

    // Full, read-only portfolio page for Admin — rich details + robust images
    public function teamPortfolioFull(UserAdminTeam $team)
    {
        // Load relations
        $team->load([
            'members' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'projects.images' => function ($q) {
                $q->orderBy('id');
            },
        ]);

        $memberCount = $team->members ? $team->members->count() : 0;

        // Team image fallback: readable, based on team name (unique per team)
        $teamPlaceholder = 'https://ui-avatars.com/api/?name=' . urlencode($team->team_name ?: 'Team') . '&background=random&size=300';
        $team->resolved_profile = $team->profile_image
            ? url('media/' . ltrim($team->profile_image, '/'))
            : $teamPlaceholder;

        // -------- Bulk map for member avatars from another details --------
        $memberIds    = $team->members->pluck('member_id')->filter()->unique()->values();
        $memberEmails = $team->members->pluck('member_email')->filter()->unique()->values();

        $users = \DB::table('users')
            ->whereIn('unique_id', $memberIds)
            ->orWhereIn('email', $memberEmails)
            ->select(['id', 'unique_id', 'email'])
            ->get();

        $emailToUid = [];
        $uidSet = collect();
        foreach ($users as $u) {
            if ($u->email)     $emailToUid[$u->email] = $u->unique_id;
            if ($u->unique_id) $uidSet->push($u->unique_id);
        }
        $uidSet = $uidSet->unique()->values();

        $uads = \DB::table('user_admin_another_details')
            ->whereIn('user_admin_id', $uidSet)
            ->select(['user_admin_id', 'profile_picture'])
            ->get()
            ->keyBy('user_admin_id');

        // Attach resolved avatar to each member (prefer uad picture; else per-user ui-avatars)
        $team->members->transform(function ($m) use ($emailToUid, $uads) {
            $uid = $m->member_id;
            if (!$uid && $m->member_email && isset($emailToUid[$m->member_email])) {
                $uid = $emailToUid[$m->member_email];
            }
            $pic = ($uid && isset($uads[$uid])) ? $uads[$uid]->profile_picture : null;

            $display = $m->member_name ?: ($m->member_email ?: ($m->member_id ?: 'User'));
            $placeholder = 'https://ui-avatars.com/api/?name=' . urlencode($display) . '&background=random&size=128';

            $m->resolved_avatar = $pic ? url('media/' . ltrim($pic, '/')) : $placeholder;
            return $m;
        });

        // Resolve project images as absolute URLs (no placeholder here; use onerror in Blade)
        $team->projects->transform(function ($p) {
            $p->resolved_images = $p->images
                ? $p->images->pluck('image_path')
                ->filter()
                ->map(fn($path) => url('media/' . ltrim($path, '/')))
                ->values()->all()
                : [];
            return $p;
        });

        $canManage = false; // read-only admin view

        return view('admin.portfolio_full', [
            'team'         => $team,
            'memberCount'  => $memberCount,
            'canManage'    => $canManage,
        ]);
    }



    /* ===================== MEMBERS (USERS TABLE) ===================== */

    public function membersList(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $perPage = max(5, min(100, (int)$request->get('per_page', 10)));

        $query = User::query()
            ->leftJoin('user_admin_another_details as uad', 'uad.user_admin_id', '=', 'users.unique_id')
            ->select('users.*', 'uad.profile_picture as uad_picture', 'uad.location as uad_location');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%")
                    ->orWhere('users.phone_number', 'like', "%{$q}%")
                    ->orWhere('users.unique_id', 'like', "%{$q}%");
            });
        }

        $sort = $request->get('sort', 'users.created_at');
        $dir  = $request->get('dir', 'desc');
        if (!in_array($sort, ['users.name', 'users.email', 'users.created_at'])) $sort = 'users.created_at';
        if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';
        $query->orderBy($sort, $dir);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = $paginator->getCollection()->map(function ($u) {
            $raw = $u->uad_picture ?? $u->profile_image ?? null;
            $avatar = $this->safeImg($raw, true);

            return [
                'id'           => $u->id,
                'unique_id'    => $u->unique_id ?? null,
                'name'         => $u->name ?? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'email'        => $u->email,
                'phone_number' => $u->phone_number ?? null,
                'location'     => $u->uad_location ?? null,
                'avatar'       => $avatar,
                'created_at'   => optional($u->created_at)->toDateTimeString(),
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function membersExport(Request $request)
    {
        $type = $request->get('type', 'csv');
        $q = trim((string)$request->get('q', ''));

        $users = User::leftJoin('user_admin_another_details as uad', 'uad.user_admin_id', '=', 'users.unique_id')
            ->select('users.*', 'uad.location as uad_location')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('users.name', 'like', "%{$q}%")
                        ->orWhere('users.email', 'like', "%{$q}%")
                        ->orWhere('users.phone_number', 'like', "%{$q}%")
                        ->orWhere('users.unique_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('users.created_at', 'desc')
            ->limit(10000)
            ->get();

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('admin.pdf.members', ['users' => $users]);
            return $pdf->download('all_members.pdf');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="all_members.csv"',
        ];

        $callback = function () use ($users) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Unique ID', 'Name', 'Email', 'Phone Number', 'Location', 'Created At']);
            foreach ($users as $u) {
                fputcsv($out, [
                    $u->id,
                    $u->unique_id,
                    $u->name ?? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                    $u->email,
                    $u->phone_number,
                    $u->uad_location,
                    optional($u->created_at)->toDateTimeString(),
                ]);
            }
            fclose($out);
        };
        return Response::stream($callback, 200, $headers);
    }

    /* ===================== TEAMS & MEMBERS ===================== */

    public function teamsList(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $perPage = max(5, min(100, (int)$request->get('per_page', 10)));

        $query = UserAdminTeam::query();

        if ($q !== '') {
            $query->where('team_name', 'like', "%{$q}%");
        }

        $query->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = $paginator->getCollection()->map(function ($t) {
            $img = $this->safeImg($t->profile_image, true);
            return [
                'id'          => $t->id,
                'team_name'   => $t->team_name,
                'profile_img' => $img,
                'about'       => strip_tags((string)$t->about),
                'created_at'  => optional($t->created_at)->toDateTimeString(),
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
    public function teamMembers(UserAdminTeam $team, Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $perPage = max(5, min(100, (int)$request->get('per_page', 10)));

        $query = \DB::table('team_members')
            ->where('team_members.team_id', $team->id)
            ->leftJoin('users', function ($join) {
                $join->on('users.unique_id', '=', 'team_members.member_id')
                    ->orOn('users.email', '=', 'team_members.member_email');
            })
            ->leftJoin('user_admin_another_details as uad', 'uad.user_admin_id', '=', 'users.unique_id')
            ->select([
                'team_members.id',
                'team_members.member_id',
                'team_members.member_email',
                'team_members.role',
                'team_members.positions',
                'team_members.status',
                'team_members.created_at',
                'uad.profile_picture as uad_picture',
            ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('team_members.member_email', 'like', "%{$q}%")
                    ->orWhere('team_members.role', 'like', "%{$q}%")
                    ->orWhere('team_members.positions', 'like', "%{$q}%")
                    ->orWhere('team_members.status', 'like', "%{$q}%")
                    ->orWhere('team_members.member_id', 'like', "%{$q}%");
            });
        }

        $query->orderBy('team_members.created_at', 'desc');

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())->map(function ($m) {
            // Build a UNIQUE placeholder per member (no local file, avoids "same for all")
            $label = $m->member_email ?: ($m->member_id ?: 'User');
            $placeholder = 'https://ui-avatars.com/api/?name=' . urlencode($label) . '&background=random&size=128';

            // Prefer uad.profile_picture from storage; else use the per-user placeholder
            $avatar = $m->uad_picture
                ? url('media/' . ltrim($m->uad_picture, '/'))
                : $placeholder;

            return [
                'id'        => $m->id,
                'name'      => $label, // or your member_name if you have it
                'email'     => $m->member_email ?: null,
                'role'      => $m->role ?: null,
                'positions' => $m->positions ?: null,
                'status'    => $m->status ?: null,
                'avatar'    => $avatar,
                'created_at' => optional($m->created_at)->toDateTimeString(),
            ];
        });


        return response()->json([
            'ok' => true,
            'team' => ['id' => $team->id, 'name' => $team->team_name],
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }



    public function teamPortfolio(UserAdminTeam $team, Request $request)
    {
        $projects = TeamProject::where('team_id', $team->id)
            ->orderBy('created_at', 'desc')
            ->with(['images' => function ($q) {
                $q->orderBy('id');
            }])
            ->paginate(10);

        $data = $projects->getCollection()->map(function ($p) {
            $imgs = $p->images ? $p->images->pluck('image_path')->map(fn($x) => $this->mediaUrl($x))->filter()->values()->all() : [];
            return [
                'id'          => $p->id,
                'title'       => $p->title ?? $p->project_title ?? 'Project',
                'description' => strip_tags((string)($p->description ?? $p->project_desc)),
                'images'      => $imgs,
                'created_at'  => optional($p->created_at)->toDateTimeString(),
            ];
        });

        return response()->json([
            'ok' => true,
            'team' => ['id' => $team->id, 'name' => $team->team_name],
            'data' => $data,
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'per_page'     => $projects->perPage(),
                'total'        => $projects->total(),
            ],
        ]);
    }

    public function teamExport(UserAdminTeam $team, Request $request)
    {
        $type = $request->get('type', 'csv');
        $scope = $request->get('scope', 'members');
        $q = trim((string)$request->get('q', ''));

        if ($scope === 'portfolio') {
            $projects = TeamProject::where('team_id', $team->id)
                ->orderBy('created_at', 'desc')
                ->with('images')
                ->get();

            if ($type === 'pdf') {
                $pdf = Pdf::loadView('admin.pdf.team_members', [
                    'team'     => $team,
                    'members'  => collect(),
                    'projects' => $projects,
                    'scope'    => 'portfolio',
                ]);
                return $pdf->download("team_{$team->id}_portfolio.pdf");
            }

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="team_' . $team->id . '_portfolio.csv',
            ];

            $callback = function () use ($projects) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Project ID', 'Title', 'Description', 'Image Count', 'Created At']);
                foreach ($projects as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->title ?? $p->project_title,
                        trim(preg_replace('/\s+/', ' ', strip_tags((string)($p->description ?? $p->project_desc)))),
                        $p->images ? $p->images->count() : 0,
                        optional($p->created_at)->toDateTimeString(),
                    ]);
                }
                fclose($out);
            };
            return Response::stream($callback, 200, $headers);
        }

        $members = TeamMember::where('team_id', $team->id)
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('member_name', 'like', "%{$q}%")
                        ->orWhere('member_email', 'like', "%{$q}%")
                        ->orWhere('role', 'like', "%{$q}%")
                        ->orWhere('positions', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%");
                });
            })
            ->orderBy('created_at', 'desc')->get();

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('admin.pdf.team_members', [
                'team'     => $team,
                'members'  => $members,
                'projects' => collect(),
                'scope'    => 'members',
            ]);
            return $pdf->download("team_{$team->id}_members.pdf");
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="team_' . $team->id . '_members.csv',
        ];

        $callback = function () use ($members) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member ID', 'Name', 'Email', 'Role', 'Positions', 'Status', 'Created At']);
            foreach ($members as $m) {
                fputcsv($out, [
                    $m->id,
                    $m->member_name,
                    $m->member_email,
                    $m->role,
                    $m->positions,
                    $m->status,
                    optional($m->created_at)->toDateTimeString(),
                ]);
            }
            fclose($out);
        };
        return Response::stream($callback, 200, $headers);
    }

    public function legalPage()
    {
        // returns the Otika view that contains only the BODY section above
        return view('admin.legal');
    }

    public function legalFetch()
    {
        $terms   = SitePage::where('slug', 'terms')->first();
        $privacy = SitePage::where('slug', 'privacy')->first();
        return response()->json([
            'terms'   => $terms,
            'privacy' => $privacy,
        ]);
    }

    public function legalSave(Request $request)
    {
        $v = Validator::make($request->all(), [
            'slug'    => ['required', 'in:terms,privacy'],
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'id'      => ['nullable', 'integer'],
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $slug    = $request->input('slug');
        $title   = $request->input('title');
        $content = $request->input('content', '');

        // upsert by slug (prefer single record per slug)
        $page = SitePage::firstOrNew(['slug' => $slug]);
        $page->title   = $title;
        $page->content = $content;
        $page->is_published = true;
        $page->save();

        return response()->json(['ok' => true, 'id' => $page->id]);
    }

    public function legalDelete($id)
    {
        $page = SitePage::find($id);
        if (!$page) return response()->json(['message' => 'Not found'], 404);
        $page->delete();
        return response()->json(['ok' => true]);
    }

    // ========== PUBLIC PAGES ==========
public function termsPagePublic()
{
    $page = \App\Models\SitePage::where('slug','terms')->first();

    return view('terms', [
        'title' => $page->title ?? 'Terms & Conditions',
        'html'  => $page->content ?? '<p>No terms published yet.</p>',
    ]);
}

public function privacyPagePublic()
{
    $page = \App\Models\SitePage::where('slug','privacy')->first();

    return view('privacy', [
        'title' => $page->title ?? 'Privacy Policy',
        'html'  => $page->content ?? '<p>No privacy policy published yet.</p>',
    ]);
}


}
