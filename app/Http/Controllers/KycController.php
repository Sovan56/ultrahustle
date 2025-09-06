<?php

namespace App\Http\Controllers;

use App\Http\Requests\KycSaveRequest;
use App\Models\User;
use App\Models\UserKycSubmission;
use Illuminate\Support\Facades\{Auth, Storage, Log, DB};
use Illuminate\Http\RedirectResponse;

class KycController extends Controller
{
    public function save(KycSaveRequest $request): RedirectResponse
    {
        $user = Auth::user() ?? User::find(session('user_id'));
        abort_if(!$user, 403);

        // If PHP dropped the files due to limits, bail early (kept from your code)
        if (!$request->hasFile('id_front') || !$request->hasFile('id_back') || !$request->hasFile('selfie')) {
            return back()
                ->withErrors(['kyc' => 'Files were not received by the server. Increase PHP/Nginx upload limits and ensure the form has enctype="multipart/form-data".'])
                ->withInput()
                ->with('tab', 'kyc');
        }

        $disk = Storage::disk('public');
        $dir  = "kyc/{$user->id}";
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $front  = $request->file('id_front');
        $back   = $request->file('id_back');
        $selfie = $request->file('selfie');

        // Unique, cache-busting filenames
        $stamp = now()->format('YmdHis');
        $frontName  = "id_front_{$stamp}." . $front->getClientOriginalExtension();
        $backName   = "id_back_{$stamp}."  . $back->getClientOriginalExtension();
        $selfieName = "selfie_{$stamp}."    . $selfie->getClientOriginalExtension();

        DB::beginTransaction();
        try {
            $frontPath  = $disk->putFileAs($dir, $front,  $frontName);
            $backPath   = $disk->putFileAs($dir, $back,   $backName);
            $selfiePath = $disk->putFileAs($dir, $selfie, $selfieName);

            UserKycSubmission::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'legal_name'    => $request->legal_name,
                    'dob'           => $request->dob,      // cast handles Date
                    'address'       => $request->address,
                    'id_type'       => $request->id_type,
                    'id_number'     => $request->id_number,
                    'id_front_path' => $frontPath,
                    'id_back_path'  => $backPath,
                    'selfie_path'   => $selfiePath,
                    'status'        => 'pending',
                    'review_notes'  => null,
                ]
            );

            DB::commit();
            return back()->with('success', 'KYC submitted. Status: pending.')->with('tab', 'kyc');
        } catch (\Throwable $e) {
            DB::rollBack();

            // Best-effort cleanup if any file wrote
            try {
                if (!empty($frontPath) && $disk->exists($frontPath)) $disk->delete($frontPath);
                if (!empty($backPath) && $disk->exists($backPath))   $disk->delete($backPath);
                if (!empty($selfiePath) && $disk->exists($selfiePath)) $disk->delete($selfiePath);
            } catch (\Throwable $ignore) {}

            Log::error('KYC_SAVE_FAIL', ['err' => $e->getMessage()]);
            return back()
                ->withErrors(['kyc' => 'Failed to save KYC: ' . $e->getMessage()])
                ->withInput()
                ->with('tab', 'kyc');
        }
    }
}
