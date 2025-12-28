<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\User;

/**
 * Risk Scoring Service
 * Sipariş risk değerlendirmesi
 */
class RiskService
{
    private const RISK_THRESHOLD = 40;

    /**
     * Calculate order risk score
     */
    public function calculateRisk(Order $order, User $user, ?string $ip = null): array
    {
        $score = 0;
        $reasons = [];

        // 1. New account check (< 1 hour)
        $accountAgeHours = $user->created_at ? $user->created_at->diffInHours(now()) : 0;
        if ($accountAgeHours < 1) {
            $score += 25;
            $reasons[] = 'Yeni hesap (1 saatten az)';
        } elseif ($accountAgeHours < 24) {
            $score += 10;
            $reasons[] = 'Hesap 24 saatten yeni';
        }

        // 2. First order
        $previousOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'completed'])
            ->count();
        if ($previousOrders === 0) {
            $score += 10;
            $reasons[] = 'İlk sipariş';
        }

        // 3. High value order (> 500 TRY)
        if ($order->amount > 500) {
            $score += 15;
            $reasons[] = 'Yüksek değerli sipariş (' . $order->amount . ' TRY)';
        } elseif ($order->amount > 250) {
            $score += 5;
            $reasons[] = 'Orta-yüksek değerli sipariş';
        }

        // 4. Multiple orders from same IP
        if ($ip) {
            $recentOrdersFromIp = Order::whereRaw("JSON_EXTRACT(meta, '$.ip') = ?", [$ip])
                ->where('created_at', '>=', now()->subHour())
                ->count();
            if ($recentOrdersFromIp > 3) {
                $score += 20;
                $reasons[] = "Aynı IP'den {$recentOrdersFromIp} sipariş (son 1 saat)";
            }
        }

        // 5. Different player IDs
        $differentPlayerIds = Order::where('user_id', $user->id)
            ->distinct()
            ->count('player_id');
        if ($differentPlayerIds > 3) {
            $score += 15;
            $reasons[] = "{$differentPlayerIds} farklı oyuncu ID kullanılmış";
        }

        // 6. Google OAuth without phone verification
        if ($user->auth_provider === 'google' && !$user->phone_verified) {
            $score += 5;
            $reasons[] = 'Google ile giriş, telefon doğrulanmamış';
        }

        // 7. Suspicious email patterns
        $emailDomain = explode('@', $user->email)[1] ?? '';
        $suspiciousProviders = ['tempmail', 'guerrilla', '10minute', 'throwaway', 'mailinator'];
        foreach ($suspiciousProviders as $provider) {
            if (str_contains($emailDomain, $provider)) {
                $score += 30;
                $reasons[] = 'Geçici e-posta sağlayıcısı';
                break;
            }
        }

        // 8. Multiple failed orders
        $failedOrders = Order::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($failedOrders >= 2) {
            $score += 15;
            $reasons[] = "{$failedOrders} başarısız sipariş (son 24 saat)";
        }

        $status = $score >= self::RISK_THRESHOLD ? 'FLAGGED' : 'CLEAR';

        return [
            'score' => min($score, 100),
            'status' => $status,
            'reasons' => $reasons,
            'calculatedAt' => now()->toISOString(),
        ];
    }
}