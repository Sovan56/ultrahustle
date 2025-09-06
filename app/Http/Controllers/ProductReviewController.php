<?php

namespace App\Http\Controllers;

use App\Models\MyOrder;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $r, Product $product)
    {
        $user = Auth::user() ?? \App\Models\User::find(session('user_id'));
        if (!$user) {
            return $this->jsonOrAbort($r, 403, 'Login required.');
        }

        // allow paid | delivered | completed
        $hasOrder = MyOrder::where('buyer_id', $user->id)
            ->where('product_id', $product->id)
            ->whereIn('status', ['paid','delivered','completed'])
            ->exists();

        if (!$hasOrder) {
            return $this->jsonOrAbort($r, 403, 'You can only review items you purchased.');
        }

        $already = ProductReview::where('product_id', $product->id)
            ->where('user_id', $user->id)->exists();
        if ($already) {
            return $this->jsonOrAbort($r, 422, 'You already reviewed this product.');
        }

        $r->validate([
            'rating_number' => 'required|integer|min:1|max:5',
            'review'        => 'nullable|string|max:2000',
            'images.*'      => 'nullable|image|max:4096',
        ]);

        $images = [];
        if ($r->hasFile('images')) {
            foreach ($r->file('images') as $file) {
                if (!$file) continue;
                $path = $file->store('reviews', 'public');
                $images[] = route('media.pass', ['path' => $path]);
            }
        }

        ProductReview::create([
            'product_id'    => $product->id,
            'user_id'       => $user->id,
            'rating_number' => (int)$r->rating_number,
            'review'        => $r->review,
            'images'        => $images,
        ]);

        if ($r->ajax() || $r->wantsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('product.details', $product->id) . '#reviews']);
        }
        return redirect()->route('product.details', $product->id) . '#reviews';
    }

    private function jsonOrAbort(Request $r, int $code, string $msg)
    {
        if ($r->ajax() || $r->wantsJson()) {
            return response()->json(['ok' => false, 'message' => $msg], $code);
        }
        abort($code, $msg);
    }
}
