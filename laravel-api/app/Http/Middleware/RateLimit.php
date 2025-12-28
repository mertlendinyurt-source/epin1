<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateLimit
{
    private array $limits = [
        '/api/auth/login' => ['limit' => 5, 'window' => 60],
        '/api/auth/register' => ['limit' => 3, 'window' => 60],
        '/api/orders' => ['limit' => 10, 'window' => 60],
        '/api/player/resolve' => ['limit' => 30, 'window' => 60],
        '/api/support' => ['limit' => 10, 'window' => 60],
        '/api/admin/settings/payments' => ['limit' => 10, 'window' => 3600], // 10 per hour
    ];

    /**
     * Handle rate limiting
     */
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        $ip = $request->ip();

        // Find matching rate limit
        $config = null;
        foreach ($this->limits as $pattern => $cfg) {
            if (str_starts_with('/api/' . $path, $pattern) || str_starts_with($path, ltrim($pattern, '/'))) {
                $config = $cfg;
                break;
            }
        }

        if (!$config) {
            return $next($request);
        }

        $key = md5($path . ':' . $ip);
        $now = time();

        // Get or create rate limit entry
        $entry = DB::table('rate_limits')->where('key', $key)->first();

        if (!$entry || ($now - strtotime($entry->window_start)) >= $config['window']) {
            // Start new window
            DB::table('rate_limits')->updateOrInsert(
                ['key' => $key],
                [
                    'count' => 1,
                    'window_start' => now(),
                    'updated_at' => now(),
                ]
            );
            return $next($request);
        }

        if ($entry->count >= $config['limit']) {
            $retryAfter = $config['window'] - ($now - strtotime($entry->window_start));
            return response()->json([
                'success' => false,
                'error' => 'Çok fazla istek',
                'message' => 'Lütfen biraz bekleyin.',
            ], 429)->header('Retry-After', $retryAfter);
        }

        // Increment counter
        DB::table('rate_limits')
            ->where('key', $key)
            ->increment('count');

        return $next($request);
    }
}