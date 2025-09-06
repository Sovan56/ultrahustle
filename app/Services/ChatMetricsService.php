<?php

namespace App\Services;

use App\Models\Message;
use Carbon\Carbon;

class ChatMetricsService
{
    /**
     * Average seconds it takes $sellerId to reply (default last 90 days).
     * We collect messages per conversation and compute gaps whenever
     * a buyer message is followed by a seller reply.
     */
    public function avgResponseSeconds(int $sellerId, int $days = 90): ?int
    {
        if ($sellerId <= 0) {
            return null;
        }

        $since = Carbon::now()->subDays($days);

        // Conversations where seller has sent at least one message in window
        $convIds = Message::where('sender_id', $sellerId)
            ->where('created_at', '>=', $since)
            ->distinct()
            ->pluck('conversation_id');

        if ($convIds->isEmpty()) {
            return null;
        }

        // Get recent messages from those conversations (windowed) ordered for pairing
        $msgs = Message::whereIn('conversation_id', $convIds)
            ->where('created_at', '>=', $since)
            ->orderBy('conversation_id')
            ->orderBy('id')
            ->get(['conversation_id', 'sender_id', 'created_at']);

        if ($msgs->isEmpty()) {
            return null;
        }

        $durations = [];
        $prevByConv = []; // conversation_id => ['sender_id'=>..., 'created_at'=>Carbon]

        foreach ($msgs as $m) {
            $cid = (int) $m->conversation_id;

            if (isset($prevByConv[$cid])) {
                $prev = $prevByConv[$cid];

                // We only measure when a buyer (not seller) message is followed by seller reply.
                if ((int)$prev['sender_id'] !== $sellerId && (int)$m->sender_id === $sellerId) {
                    $sec = $m->created_at->diffInSeconds($prev['created_at'], false);
                    // keep only non-negative up to 7 days
                    if ($sec >= 0 && $sec <= 7 * 24 * 3600) {
                        $durations[] = $sec;
                    }
                }
            }

            // advance the cursor for that conversation
            $prevByConv[$cid] = [
                'sender_id'  => (int) $m->sender_id,
                'created_at' => $m->created_at,
            ];
        }

        if (empty($durations)) {
            return null;
        }

        return (int) floor(array_sum($durations) / count($durations));
    }

    /**
     * Human readable time with pluralization and larger units.
     * Examples: 45s, 12 min, 3 hours, 2 days, 5 months, 1 year
     */
    public static function human(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        if ($seconds < 3600) {
            $m = (int) floor($seconds / 60);
            return $m . ' min';
        }
        if ($seconds < 86400) {
            $h = (int) floor($seconds / 3600);
            return $h . ' ' . ($h === 1 ? 'hour' : 'hours');
        }
        if ($seconds < 2592000) { // < 30 days
            $d = (int) floor($seconds / 86400);
            return $d . ' ' . ($d === 1 ? 'day' : 'days');
        }
        if ($seconds < 31536000) { // < 365 days
            $mo = (int) floor($seconds / 2592000);
            return $mo . ' ' . ($mo === 1 ? 'month' : 'months');
        }
        $y = (int) floor($seconds / 31536000);
        return $y . ' ' . ($y === 1 ? 'year' : 'years');
    }
}
