<?php

namespace App\Http\Controllers;

use App\Mail\TeamDeletedMail;
use App\Mail\TeamInviteMail;
use App\Mail\TeamRemovedMail;
use App\Models\TeamMember;
use App\Models\UserAdminTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\TeamProject;
use App\Models\TeamProjectImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MyTeamController extends Controller
{
    private function currentUserId(): string
    {
        // Numeric users.id stored in session after login
        return (string) session('user_id');
    }

    private function currentUserUniqueId(): ?string
    {
        // Cache once per request (or stash in session if you want)
        static $uid = null;
        if ($uid !== null) return $uid;

        $id = session('user_id');
        if (!$id) return null;

        $uid = DB::table('users')->where('id', $id)->value('unique_id');
        return $uid ? (string) $uid : null;
    }

    // ========== Page ==========
    public function page()
    {
        return view('UserAdmin.myteam');
    }

    // ========== AJAX: list teams (owned + joined) ==========
    public function teams(Request $request)
    {
        $q          = $request->get('q');
        $ownerId    = $this->currentUserId();       // compare with team_owner_id
        $uniqueId   = $this->currentUserUniqueId(); // compare with team_members.member_id

        // team IDs where current user (by unique_id) is an accepted member
        $joinedIds = TeamMember::where('member_id', $uniqueId)
            ->where('status', 'accepted')
            ->pluck('team_id');

        $teams = UserAdminTeam::query()
            ->where(function ($w) use ($ownerId, $joinedIds) {
                $w->where('team_owner_id', $ownerId)
                  ->orWhereIn('id', $joinedIds);
            })
            ->when($q, fn ($w) => $w->where('team_name', 'like', "%$q%"))
            ->latest('id')
            ->get()
            // add owner flag for frontend controls
            ->map(function ($t) use ($ownerId) {
                $t->is_owner = ($t->team_owner_id === $ownerId);
                return $t;
            })
            ->values();

        return response()->json(['ok' => true, 'data' => $teams]);
    }

    // ========== AJAX: create team ==========
    public function createTeam(Request $request)
    {
        $request->validate([
            'team_name'     => 'required|string|max:255',
            'about'         => 'nullable|string|max:2000',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('teams', 'public');
        }

        $team = UserAdminTeam::create([
            'team_name'     => $request->team_name,
            'team_owner_id' => $this->currentUserId(),
            'profile_image' => $path,
            'about'         => $request->input('about'),
        ]);

        return response()->json(['ok' => true, 'data' => $team]);
    }

    // ========== AJAX: update team ==========
    public function updateTeam(Request $request, UserAdminTeam $team)
    {
        $this->authorizeTeam($team);

        $request->validate([
            'team_name'     => 'required|string|max:255',
            'about'         => 'nullable|string|max:2000',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            if ($team->profile_image) {
                Storage::disk('public')->delete($team->profile_image);
            }
            $team->profile_image = $request->file('profile_image')->store('teams', 'public');
        }

        $team->team_name = $request->team_name;
        $team->about     = $request->input('about');
        $team->save();

        return response()->json(['ok' => true, 'data' => $team]);
    }

    // ========== AJAX: delete team (notify by email) ==========
    public function deleteTeam(UserAdminTeam $team)
    {
        $this->authorizeTeam($team);

        $emails = $team->members()
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('member_email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new TeamDeletedMail($team, $email));
            } catch (\Throwable $e) {}
        }

        if ($team->profile_image) {
            Storage::disk('public')->delete($team->profile_image);
        }
        $team->delete();

        return response()->json(['ok' => true]);
    }

    // ========== AJAX: team details + members ==========
    public function members(Request $request, UserAdminTeam $team)
    {
        $this->authorizeTeamRead($team);

        $ownerId  = $this->currentUserId();
        $isOwner  = ($team->team_owner_id === $ownerId);
        $q        = $request->get('q');

        // join user_admin_another_details on member unique_id for avatar
        $membersQuery = $team->members()
            ->select('team_members.*')
            ->leftJoin('user_admin_another_details as uad', 'uad.user_admin_id', '=', 'team_members.member_id')
            ->addSelect('uad.profile_picture');

        if (!$isOwner) {
            // members can only see accepted members
            $membersQuery->where('team_members.status', 'accepted');
        }

        $members = $membersQuery
            ->when($q, function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('team_members.positions', 'like', "%$q%")
                      ->orWhere('team_members.member_email', 'like', "%$q%")
                      ->orWhere('team_members.member_id', 'like', "%$q%");
                });
            })
            ->orderBy('team_members.id', 'desc')
            ->get();

        $joined  = $team->members()->where('status', 'accepted')->count();
        $pending = $isOwner ? $team->members()->where('status', 'pending')->count() : 0;

        return response()->json([
            'ok'   => true,
            'data' => [
                'team'      => $team,
                'members'   => $members,
                'counts'    => ['joined' => $joined, 'pending' => $pending],
                'canManage' => $isOwner,
            ],
        ]);
    }

    // ========== AJAX: add members (multiple email+position) ==========
    public function addMembers(Request $request, UserAdminTeam $team)
    {
        $this->authorizeTeam($team);

        $request->validate([
            'pairs'            => 'required|array|min:1',
            'pairs.*.email'    => 'required|email',
            'pairs.*.position' => 'nullable|string|max:255',
            'pairs.*.role'     => 'required|in:admin,user',
        ]);

        $pairs   = $request->input('pairs', []);
        $inviter = $this->currentUserId();
        $created = [];

        DB::beginTransaction();
        try {
            foreach ($pairs as $pair) {
                $email    = strtolower(trim($pair['email'] ?? ''));
                if (!$email) { continue; }

                $position = $pair['position'] ?? null;
                $role     = $pair['role'] ?? 'user';

                $existsPending = TeamMember::where('team_id', $team->id)
                    ->where('member_email', $email)
                    ->where('status', 'pending')
                    ->exists();

                if ($existsPending) continue;

                $invite = TeamMember::create([
                    'team_id'      => $team->id,
                    'positions'    => $position,
                    'role'         => $role,
                    'member_id'    => null,
                    'member_email' => $email,
                    'status'       => 'pending',
                    'invite_token' => \Str::random(40),
                    'invited_at'   => now(),
                    'responded_at' => null,
                    'invited_by'   => $inviter,
                ]);

                $acceptUrl = url('/invites/accept/' . $invite->invite_token);
                try {
                    \Mail::to($email)->send(new \App\Mail\TeamInviteMail($team, $invite, $acceptUrl));
                } catch (\Throwable $e) {}

                $created[] = $invite;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => 'Failed to invite members'], 422);
        }

        return response()->json(['ok' => true, 'data' => $created]);
    }

    // ========== AJAX: remove member (invalidate token) ==========
    public function removeMember(UserAdminTeam $team, TeamMember $member)
    {
        $this->authorizeTeam($team);

        if ($member->team_id !== $team->id) {
            return response()->json(['ok' => false, 'message' => 'Invalid member'], 404);
        }

        $member->status        = 'removed';
        $member->invite_token  = null;
        $member->responded_at  = now();
        $member->save();

        if ($member->member_email) {
            try {
                Mail::to($member->member_email)->send(new TeamRemovedMail($team, $member));
            } catch (\Throwable $e) {}
        }

        return response()->json(['ok' => true]);
    }

    // ========== Resend invite ==========
    public function resendInvite(UserAdminTeam $team, TeamMember $member)
    {
        $this->authorizeTeam($team);

        if ($member->team_id !== $team->id || $member->status !== 'pending') {
            return response()->json(['ok' => false, 'message' => 'Invite not pending'], 422);
        }

        if (!$member->invite_token) {
            $member->invite_token = Str::random(40);
            $member->invited_at   = now();
            $member->save();
        }

        $acceptUrl = url('/invites/accept/' . $member->invite_token);
        try {
            Mail::to($member->member_email)->send(new TeamInviteMail($team, $member, $acceptUrl));
        } catch (\Throwable $e) {}

        return response()->json(['ok' => true]);
    }

    // ========== Accept invite (member_id = users.unique_id) ==========
    public function acceptInvite(Request $request, string $token)
    {
        $invite = TeamMember::where('invite_token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invite) {
            return view('auth.invite_result', [
                'message' => 'This invite is no longer valid (used, expired, or removed).',
            ]);
        }

        if (!session()->has('user_id')) {
            session(['pending_team_invite' => $token]);
            return redirect()->route('login');
        }

        $uniqueId = $this->currentUserUniqueId();
        if (!$uniqueId) {
            return view('auth.invite_result', ['message' => 'User not found.']);
        }

        $dup = TeamMember::where('team_id', $invite->team_id)
            ->where('member_id', $uniqueId)
            ->where('status', 'accepted')
            ->exists();

        if ($dup) {
            $invite->status       = 'removed';
            $invite->responded_at = now();
            $invite->save();

            return view('auth.invite_result', [
                'message' => 'You are already part of this team.',
            ]);
        }

        $invite->member_id    = $uniqueId;
        $invite->status       = 'accepted';
        $invite->responded_at = now();
        $invite->invite_token = null;
        $invite->save();

        return view('auth.invite_result', [
            'message' => 'Invitation accepted. You have joined the team.',
        ]);
    }

    // ========== Authorization helpers ==========
    private function authorizeTeam(UserAdminTeam $team): void
    {
        if ($team->team_owner_id !== $this->currentUserId()) {
            abort(403, 'Not allowed');
        }
    }

    private function authorizeTeamRead(UserAdminTeam $team): void
    {
        if ($team->team_owner_id === $this->currentUserId()) return;

        $uniqueId = $this->currentUserUniqueId();
        $canRead = TeamMember::where('team_id', $team->id)
            ->where('member_id', $uniqueId)
            ->where('status', 'accepted')
            ->exists();

        if (!$canRead) abort(403, 'Not allowed');
    }

    // ---------- PAGE: CREATE ----------
    public function pageCreate(Request $request) {
        return view('UserAdmin.team_form', [
            'mode' => 'create',
            'team' => new UserAdminTeam(),
        ]);
    }

    // ---------- PAGE: EDIT ----------
    public function pageEdit(Request $request, UserAdminTeam $team) {
        $this->authorizeTeamOwner($team);
        $team->load(['projects.images']);
        return view('UserAdmin.team_form', [
            'mode' => 'edit',
            'team' => $team,
        ]);
    }

    // ---------- PAGE: PORTFOLIO ----------
    public function pagePortfolio(Request $request, UserAdminTeam $team) {
    $team->load([
        'members' => function ($q) {
            $q->orderBy('role')->orderBy('positions');
        },
        'projects.images'
    ]);

    $memberCount = $team->members()->count();

    // ✅ Only the owner (team_owner_id) should see manage / edit buttons
    $canManage = ((string) $team->team_owner_id === (string) session('user_id'));

    return view('UserAdmin.team_portfolio', [
        'team'        => $team,
        'memberCount' => $memberCount,
        'canManage'   => $canManage,   // <-- add this
    ]);
}


    // ------------------ PROJECTS: LIST ------------------
    public function projects(Request $request, UserAdminTeam $team) {
        $this->authorizeTeamOwner($team);
        $data = $team->projects()->with('images')->orderBy('id', 'desc')->get();
        return response()->json(['data' => $data]);
    }

    // ------------------ PROJECTS: CREATE (single or multiple) ------------------
    public function projectStore(Request $request, UserAdminTeam $team) {
        $this->authorizeTeamOwner($team);

        if ($request->has('projects')) {
            $projects = $request->input('projects');
            if (!is_array($projects) || empty($projects)) {
                throw ValidationException::withMessages(['projects' => 'Invalid projects payload']);
            }

            $created = [];
            foreach ($projects as $idx => $payload) {
                $title = data_get($payload, 'title');
                $description = data_get($payload, 'description');

                if (!$title || !is_string($title)) {
                    throw ValidationException::withMessages(["projects.$idx.title" => 'Title is required']);
                }
                if ($description && !is_string($description)) {
                    throw ValidationException::withMessages(["projects.$idx.description" => 'Invalid description']);
                }

                $proj = TeamProject::create([
                    'team_id'     => $team->id,
                    'title'       => $title,
                    'description' => $description,
                ]);

                $files = $request->file("projects.$idx.images", []);
                foreach ($files as $img) {
                    if (!$img->isValid()) continue;
                    $name = Str::uuid()->toString().'.'.$img->getClientOriginalExtension();
                    $path = "team_projects/{$team->id}/{$proj->id}/{$name}";
                    \Storage::disk('public')->put($path, file_get_contents($img));
                    TeamProjectImage::create([
                        'project_id' => $proj->id,
                        'image_path' => $path,
                    ]);
                }

                $created[] = $proj->load('images');
            }

            return response()->json(['message' => 'Projects created', 'data' => $created], 201);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'images.*'    => 'nullable|image|max:4096',
        ]);

        $project = TeamProject::create([
            'team_id'     => $team->id,
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                if (!$img->isValid()) continue;
                $name = Str::uuid()->toString().'.'.$img->getClientOriginalExtension();
                $path = "team_projects/{$team->id}/{$project->id}/{$name}";
                \Storage::disk('public')->put($path, file_get_contents($img));
                TeamProjectImage::create([
                    'project_id' => $project->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json(['message' => 'Project created', 'data' => $project->load('images')], 201);
    }

    // ------------------ PROJECTS: UPDATE (title/description only) ------------------
    public function projectUpdate(Request $request, UserAdminTeam $team, TeamProject $project) {
        $this->authorizeTeamOwner($team);
        if ($project->team_id !== $team->id) abort(404);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $project->update([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'Project updated', 'data' => $project->refresh()->load('images')]);
    }

    // ------------------ PROJECTS: DELETE (with files) ------------------
    public function projectDelete(Request $request, UserAdminTeam $team, TeamProject $project) {
        $this->authorizeTeamOwner($team);
        if ($project->team_id !== $team->id) abort(404);

        $folder = "team_projects/{$team->id}/{$project->id}";
        \Storage::disk('public')->deleteDirectory($folder);
        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    // ------------------ PROJECTS: ADD IMAGES ------------------
    public function projectImagesAdd(Request $request, UserAdminTeam $team, TeamProject $project) {
        $this->authorizeTeamOwner($team);
        if ($project->team_id !== $team->id) abort(404);

        $request->validate([
            'images.*' => 'required|image|max:4096',
        ]);

        $created = [];
        foreach ($request->file('images', []) as $img) {
            if (!$img->isValid()) continue;
            $name = Str::uuid()->toString().'.'.$img->getClientOriginalExtension();
            $path = "team_projects/{$team->id}/{$project->id}/{$name}";
            \Storage::disk('public')->put($path, file_get_contents($img));
            $created[] = TeamProjectImage::create([
                'project_id' => $project->id,
                'image_path' => $path,
            ]);
        }

        return response()->json(['message' => 'Images added', 'data' => $created]);
    }

    // ------------------ PROJECTS: DELETE ONE IMAGE ------------------
    public function projectImagesDelete(Request $request, UserAdminTeam $team, TeamProject $project, TeamProjectImage $image) {
        $this->authorizeTeamOwner($team);
        if ($project->team_id !== $team->id || $image->project_id !== $project->id) abort(404);

        \Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json(['message' => 'Image removed']);
    }

    // ---------- helper ----------
    private function authorizeTeamOwner(UserAdminTeam $team): void {
        $currentUserId = session('user_id');
        if ((string)$team->team_owner_id !== (string)$currentUserId) {
            abort(403, 'Not authorized');
        }
    }
}
