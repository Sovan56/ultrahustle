<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    public function redirect(Message $message, int $index = 0)
    {
        $user = auth()->user();
        abort_unless($user && $message->conversation?->hasUser($user->id), 403);

        $path = $message->file_path;
        if (!$path) abort(404);

        // Serve from public if it exists there
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        // Otherwise from local/private
        if (Storage::disk('local')->exists($path)) {
            // Force download (or you could stream)
            return Storage::disk('local')->download($path, $message->file_name ?? basename($path));
        }

        abort(404);
    }
}
