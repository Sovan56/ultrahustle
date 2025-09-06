<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

// app/Http/Controllers/ProductDetailsController.php
class ProductDetailsController extends Controller
{
    public function show(int $id)
    {
        $product = Product::with([
            'user',
            'type',
            'subcategory',
            'pricings' => fn($q) => $q->orderByRaw("FIELD(tier,'basic','standard','premium')")
        ])->findOrFail($id);

        $faqs = $product->faqs()->orderBy('id')->get();

        $reviews = $product->reviews()->with('user')->latest()->limit(20)->get();
        $ratingAvg = (float) $product->reviews()->avg('rating_number');
        $ratingCount = (int) $product->reviews()->count();

        return view('ProductDetails', compact('product','faqs','reviews','ratingAvg','ratingCount'));
    }
}

