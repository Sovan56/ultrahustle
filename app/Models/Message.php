<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function isImage(): bool
{
    $m = $this->file_mime ?? $this->mime_type ?? null;
    if (is_string($m) && str_starts_with($m, 'image/')) return true;

    if ($this->file_name) {
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','svg']);
    }
    return false;
}


    public function publicUrl(): ?string
    {
        if (! $this->file_path) return null;

        // If saved on public disk, serve via /media/ passthrough (already in routes)
        if (Storage::disk('public')->exists($this->file_path)) {
            return route('media.pass', ['path' => ltrim($this->file_path,'/')]);
        }

        // Otherwise use the chat attachment redirect route
        return route('chat.attachments.show', ['message'=>$this->id, 'index'=>0]);
    }
}
