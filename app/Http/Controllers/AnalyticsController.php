<?php
// app/Http/Controllers/AnalyticsController.php

namespace App\Http\Controllers;

use App\Models\ProductClick;
use App\Models\ProductView;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// top of file
use Illuminate\Support\Facades\Schema;
use App\Models\ProductInsight;


class AnalyticsController extends Controller
{
    // POST /a/click
    public function click(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'source'     => 'nullable|string|max:50',
        ]);

        ProductClick::create([
            'product_id' => (int)$request->product_id,
            'user_id'    => optional($request->user())->id,
            'source'     => $request->input('source','welcome'),
            'ip'         => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);

        return response()->noContent(); // 204
    }

    // Page (Otika-styled)
    public function page()
    {
        return view('UserAdmin.boost_analytics');
    }

    // JSON: last 30 days daily views + clicks for the current user's boosted products
public function daily(\Illuminate\Http\Request $request)
{
    $userId = (string) auth()->id();

    $productIds = \App\Models\Product::query()
        ->whereRaw('CAST(user_id AS CHAR) = ?', [$userId])
        ->pluck('id');

    $from = now()->subDays(29)->startOfDay();
    $to   = now()->endOfDay();

    $labels = [];
    $viewsData = [];
    $clicksData = [];

    if (Schema::hasTable('product_insights')) {
        $rows = \DB::table('product_insights')
            ->whereIn('product_id', $productIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('date as d, SUM(views) as v, SUM(clicks) as c')
            ->groupBy('d')->orderBy('d')->get()->keyBy('d');

        for ($i=0; $i<30; $i++) {
            $day = now()->subDays(29 - $i)->toDateString();
            $labels[]    = $day;
            $viewsData[] = (int) ($rows[$day]->v ?? 0);
            $clicksData[]= (int) ($rows[$day]->c ?? 0);
        }

        return response()->json(compact('labels','viewsData','clicksData'));
    }

    // Fallback to raw events (previous implementation)
    $views = \App\Models\ProductView::query()
        ->whereIn('product_id', $productIds)
        ->whereBetween('created_at', [$from,$to])
        ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
        ->groupBy('d')->orderBy('d')->get()->keyBy('d');

    $clicks = \App\Models\ProductClick::query()
        ->whereIn('product_id', $productIds)
        ->whereBetween('created_at', [$from,$to])
        ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
        ->groupBy('d')->orderBy('d')->get()->keyBy('d');

    for ($i=0; $i<30; $i++) {
        $day = now()->subDays(29 - $i)->toDateString();
        $labels[]    = $day;
        $viewsData[] = (int) ($views[$day]->c ?? 0);
        $clicksData[]= (int) ($clicks[$day]->c ?? 0);
    }

    return response()->json(compact('labels','viewsData','clicksData'));
}


    // JSON: top products by views/clicks (last 30 days)
   public function top(\Illuminate\Http\Request $request)
{
    $userId = (string) auth()->id();
    $from = now()->subDays(29)->toDateString();
    $to   = now()->toDateString();

    $base = \App\Models\Product::query()
        ->whereRaw('CAST(user_id AS CHAR) = ?', [$userId])
        ->pluck('id');

    if (\Illuminate\Support\Facades\Schema::hasTable('product_insights')) {
        $agg = \DB::table('product_insights')
            ->whereIn('product_id', $base)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('product_id, SUM(views) as views, SUM(clicks) as clicks')
            ->groupBy('product_id');

        $rows = \DB::table('products as p')
            ->leftJoinSub($agg, 'a', 'a.product_id', '=', 'p.id')
            ->selectRaw('p.id, p.name, COALESCE(a.views,0) as views, COALESCE(a.clicks,0) as clicks')
            ->orderByDesc('views')->orderByDesc('clicks')->limit(10)->get();

        return response()->json(['rows' => $rows]);
    }

    // Fallback to raw events (previous implementation)
    $views = \App\Models\ProductView::query()
        ->whereIn('product_id', $base)
        ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
        ->selectRaw('product_id, COUNT(*) as views')->groupBy('product_id');

    $clicks = \App\Models\ProductClick::query()
        ->whereIn('product_id', $base)
        ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
        ->selectRaw('product_id, COUNT(*) as clicks')->groupBy('product_id');

    $rows = \DB::table('products as p')
        ->leftJoinSub($views, 'v', 'v.product_id', '=', 'p.id')
        ->leftJoinSub($clicks, 'c', 'c.product_id', '=', 'p.id')
        ->selectRaw('p.id, p.name, COALESCE(v.views,0) as views, COALESCE(c.clicks,0) as clicks')
        ->orderByDesc('views')->orderByDesc('clicks')->limit(10)->get();

    return response()->json(['rows' => $rows]);
}

}
