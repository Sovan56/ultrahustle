<?php

namespace App\Http\Controllers;

use App\Models\UserKycSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminKycController extends Controller
{
    public function index()
    {
        return view('admin.KycRequest');
    }

    public function list(Request $request)
    {
        $status = $request->query('status', 'pending');

        $base = UserKycSubmission::query()->with('user');
        $totalAll = (clone $base)->count();

        $q = $base->when(in_array($status, ['pending','approved','rejected']),
            fn($qr) => $qr->where('status', $status));

        // DataTables search
        if ($search = $request->input('search.value')) {
            $q->where(function($qq) use ($search) {
                $qq->where('legal_name','like',"%{$search}%")
                   ->orWhere('id_number','like',"%{$search}%")
                   ->orWhereHas('user', function($u) use ($search) {
                       $u->where('first_name','like',"%{$search}%")
                         ->orWhere('last_name','like',"%{$search}%")
                         ->orWhere('email','like',"%{$search}%");
                   });
            });
        }

        $totalFiltered = (clone $q)->count();

        // Paging
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $rows = $q->orderByDesc('id')->skip($start)->take($length)->get();

        $data = $rows->map(function(UserKycSubmission $k) {
            $user = $k->user;
            $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User');
            $idMasked = $k->id_number ? (Str::mask($k->id_number, '*', 2, max(0, strlen($k->id_number) - 6))) : '—';

            // Try user->detail->profile_picture; fallback to selfie; fallback to default
            $avatar = optional($user->detail)->profile_picture ?: $k->selfie_path;
            $avatarUrl = $avatar ? url('/media/'.ltrim($avatar,'/')) : asset('assets/img/users/user-1.png');

            $viewUrl = route('admin.kyc.show', $k->id);

            $actions = '
              <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-info" target="_blank" href="'.$viewUrl.'">View</a>
                <button type="button" class="btn btn-success js-approve" data-id="'.$k->id.'">Approve</button>
                <button type="button" class="btn btn-danger js-reject" data-id="'.$k->id.'">Reject</button>
              </div>';

            return [
                'id'         => $k->id,                  // <— include id
                'view_url'   => $viewUrl,                // <— include view URL
                'user'       => '<div class="d-flex align-items-center"><img src="'.$avatarUrl.'" class="rounded" style="width:32px;height:32px;object-fit:cover;margin-right:8px;"><div><div>'.e($fullName).'</div><div class="text-muted small">'.e($user->email).'</div></div></div>',
                'legal_name' => e($k->legal_name),
                'id_type'    => e($k->id_type),
                'id_number'  => e($idMasked),
                'status'     => '<span class="badge badge-'.($k->status==='approved'?'success':($k->status==='rejected'?'danger':'warning')).'">'.e($k->status).'</span>',
                'created_at' => optional($k->created_at)->format('Y-m-d H:i'),
                'actions'    => $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalAll,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    public function show(UserKycSubmission $kyc)
    {
        $kyc->load('user');
        return view('admin.KycShow', compact('kyc'));
    }

    public function updateStatus(Request $request, UserKycSubmission $kyc)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $kyc->update([
            'status' => $request->input('status'),
            'review_notes' => $request->input('review_notes'),
        ]);

        return response()->json(['success' => true]);
    }
}
