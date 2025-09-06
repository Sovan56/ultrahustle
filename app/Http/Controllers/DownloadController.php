<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    public function digital(int $order, int $index)   { return $this->serveOrderFile($order, $index); }
    public function order(int $order, int $index)     { return $this->serveOrderFile($order, $index); }
    public function orderFile(int $order, int $index) { return $this->serveOrderFile($order, $index); }

    protected function serveOrderFile(int $orderId, int $index)
    {
        $buyerId = Auth::id() ?: (int) session('user_id');
        abort_unless($buyerId, 403);

        $row = DB::table('my_orders as mo')
            ->leftJoin('products as p', 'p.id', '=', 'mo.product_id')
            ->where('mo.id', $orderId)
            ->where('mo.buyer_id', $buyerId)
            ->select(['mo.delivery_files', 'p.files as product_files'])
            ->first();

        abort_unless($row, 404, 'Order not found');

        // ---- prefer PRODUCT files first (JSON of relative paths) ----
        $productFiles = json_decode($row->product_files ?? '[]', true) ?: [];
        $orderFiles   = json_decode($row->delivery_files ?? '[]', true) ?: [];
        $rawList      = !empty($productFiles) ? $productFiles : $orderFiles;

        // Build a flat list of strings (paths/URLs)
        $paths = [];
        foreach ($rawList as $entry) {
            if (is_string($entry)) {
                $paths[] = $entry;
            } elseif (is_array($entry)) {
                $paths[] = $entry['url']
                    ?? $entry['path']
                    ?? $entry['storage_path']
                    ?? $entry['key']
                    ?? $entry['media']
                    ?? null;
            }
        }
        $paths = array_values(array_filter($paths, fn($v) => is_string($v) && $v !== ''));

        abort_unless(array_key_exists($index, $paths), 404, 'File index not found');

        $target = $paths[$index];

        // Absolute URL? redirect (covers presigned S3/CloudFront/etc.)
        if (Str::startsWith($target, ['http://', 'https://'])) {
            return redirect()->away($target);
        }

        // If it’s a /media URL, use your passthrough
        if (Str::startsWith($target, ['/media/', 'media/'])) {
            $mediaPath = ltrim(Str::after($target, '/media/'), '/');
            return redirect()->route('media.pass', ['path' => $mediaPath]);
        }

        // Normalize common local patterns to public disk
        $path = ltrim($target, '/');
        if (Str::startsWith($path, 'storage/')) $path = Str::after($path, 'storage/');
        if (Str::startsWith($path, 'public/'))  $path = Str::after($path, 'public/');

        // Try public disk (storage/app/public)
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, basename($path));
        }

        // Try default disk
        if (Storage::exists($path)) {
            return Storage::download($path, basename($path));
        }

        // Try a few common prefixes just in case product paths were stored without them
        foreach (["products/{$path}", "uploads/{$path}", "files/{$path}"] as $cand) {
            if (Storage::disk('public')->exists($cand)) {
                return Storage::disk('public')->download($cand, basename($cand));
            }
        }

        // Last resort: public_path or /media passthrough
        $full = public_path($target);
        if (is_file($full)) {
            return response()->download($full, basename($full));
        }

        return redirect()->route('media.pass', ['path' => $path]);
    }
}
