<?php

namespace App\Http\Controllers;

use App\Models\ProductInsight;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductTrackingController extends Controller
{
    public function trackView(int $id)
    {
        $date = Carbon::today();
        $row = ProductInsight::firstOrCreate(
            ['product_id' => $id, 'event_date' => $date],
            ['views' => 0, 'clicks' => 0]
        );
        $row->increment('views');
        return response()->json(['ok' => true]);
    }

    public function clickAndGo(int $id)
    {
        $date = Carbon::today();
        $row = ProductInsight::firstOrCreate(
            ['product_id' => $id, 'event_date' => $date],
            ['views' => 0, 'clicks' => 0]
        );
        $row->increment('clicks');

        return redirect()->route('product.details', ['id' => $id]);
    }
}
