<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ForumUploadController extends Controller
{
    public function store(Request $req)
    {
        if (!auth()->check()) return response()->json(['message'=>'Login required'], 401);

        $req->validate(['file' => ['required','file','image','max:4096']]);
        $path = $req->file('file')->store('forum/uploads', 'public');
        return response()->json(['url' => route('media.pass', ['path' => $path])]);
    }
}
