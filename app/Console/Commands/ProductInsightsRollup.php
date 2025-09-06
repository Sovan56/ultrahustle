<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductInsightsRollup extends Command
{
    protected $signature = 'insights:rollup {--days=30} {--product_id=}';
    protected $description = 'Roll up product views/clicks into daily product_insights';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $from = now()->subDays($days - 1)->startOfDay();
        $to   = now()->endOfDay();
        $pid  = $this->option('product_id');

        $viewsQ = DB::table('product_views')
            ->selectRaw('product_id, DATE(created_at) as d, COUNT(*) as c')
            ->whereBetween('created_at', [$from, $to]);
        if ($pid) $viewsQ->where('product_id', (int)$pid);
        $views = $viewsQ->groupBy('product_id', 'd')->get();

        $clicksQ = DB::table('product_clicks')
            ->selectRaw('product_id, DATE(created_at) as d, COUNT(*) as c')
            ->whereBetween('created_at', [$from, $to]);
        if ($pid) $clicksQ->where('product_id', (int)$pid);
        $clicks = $clicksQ->groupBy('product_id', 'd')->get();

        $rows = []; // key: "product_id|date"
        $now = now();
        foreach ($views as $r) {
            $k = $r->product_id.'|'.$r->d;
            $rows[$k] = $rows[$k] ?? [
                'product_id'  => $r->product_id,
                'date'        => $r->d,
                'views'       => 0,
                'clicks'      => 0,
                'impressions' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
            $rows[$k]['views'] = (int) $r->c;
        }
        foreach ($clicks as $r) {
            $k = $r->product_id.'|'.$r->d;
            $rows[$k] = $rows[$k] ?? [
                'product_id'  => $r->product_id,
                'date'        => $r->d,
                'views'       => 0,
                'clicks'      => 0,
                'impressions' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
            $rows[$k]['clicks'] = (int) $r->c;
        }

        if ($rows) {
            DB::table('product_insights')->upsert(
                array_values($rows),
                ['product_id', 'date'],
                ['views', 'clicks', 'impressions', 'updated_at']
            );
        }

        $this->info('Rolled up '.count($rows).' day-buckets.');
        return self::SUCCESS;
    }
}
