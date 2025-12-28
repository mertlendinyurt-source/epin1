<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Region;
use App\Models\Review;
use App\Models\GameContent;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    /**
     * Health check
     * GET /api/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'version' => '1.0.0',
            'time' => now()->toISOString(),
            'uptime' => 0,
        ]);
    }

    /**
     * API root
     * GET /api
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'PINLY API v1.0',
            'status' => 'ok',
            'version' => '1.0.0',
        ]);
    }

    /**
     * Get all active products
     * GET /api/products
     */
    public function products(): JsonResponse
    {
        $products = Product::where('active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products->map->toApiArray(),
        ]);
    }

    /**
     * Get enabled regions
     * GET /api/regions
     */
    public function regions(): JsonResponse
    {
        $regions = Region::where('enabled', true)
            ->orderBy('sort_order')
            ->get();

        // Default regions if none exist
        if ($regions->isEmpty()) {
            $defaults = [
                ['id' => 'tr', 'code' => 'TR', 'name' => 'Türkiye', 'enabled' => true, 'flagImageUrl' => null, 'sortOrder' => 1],
                ['id' => 'global', 'code' => 'GLOBAL', 'name' => 'Küresel', 'enabled' => true, 'flagImageUrl' => null, 'sortOrder' => 2],
                ['id' => 'de', 'code' => 'DE', 'name' => 'Almanya', 'enabled' => true, 'flagImageUrl' => null, 'sortOrder' => 3],
                ['id' => 'fr', 'code' => 'FR', 'name' => 'Fransa', 'enabled' => true, 'flagImageUrl' => null, 'sortOrder' => 4],
                ['id' => 'jp', 'code' => 'JP', 'name' => 'Japonya', 'enabled' => true, 'flagImageUrl' => null, 'sortOrder' => 5],
            ];
            return response()->json(['success' => true, 'data' => $defaults]);
        }

        return response()->json([
            'success' => true,
            'data' => $regions->map->toApiArray(),
        ]);
    }

    /**
     * Get site settings
     * GET /api/site/settings
     */
    public function siteSettings(): JsonResponse
    {
        $settings = SiteSettings::getActive();

        $data = $settings ? $settings->toApiArray() : [
            'siteName' => 'PINLY',
            'metaTitle' => 'PINLY – Dijital Kod ve Oyun Satış Platformu',
            'metaDescription' => 'PUBG Mobile UC satın al. Güvenilir, hızlı ve uygun fiyatlı UC satış platformu.',
            'contactEmail' => '',
            'contactPhone' => '',
            'logo' => null,
            'favicon' => null,
            'heroImage' => null,
            'categoryIcon' => null,
            'dailyBannerEnabled' => true,
            'dailyBannerTitle' => 'Bugüne Özel Fiyatlar',
            'dailyBannerSubtitle' => '',
            'dailyBannerIcon' => 'fire',
            'dailyCountdownEnabled' => true,
            'dailyCountdownLabel' => 'Kampanya bitimine',
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get banner settings
     * GET /api/site/banner
     */
    public function bannerSettings(): JsonResponse
    {
        $settings = SiteSettings::getActive();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $settings?->daily_banner_enabled ?? true,
                'title' => $settings?->daily_banner_title ?? 'Bugüne Özel Fiyatlar',
                'subtitle' => $settings?->daily_banner_subtitle ?? '',
                'icon' => $settings?->daily_banner_icon ?? 'fire',
                'countdownEnabled' => $settings?->daily_countdown_enabled ?? true,
                'countdownLabel' => $settings?->daily_countdown_label ?? 'Kampanya bitimine',
            ],
        ]);
    }

    /**
     * Get game content (PUBG)
     * GET /api/content/pubg
     */
    public function gameContent(): JsonResponse
    {
        $content = GameContent::find('pubg');

        if (!$content) {
            $content = [
                'game' => 'pubg',
                'title' => 'PUBG Mobile',
                'description' => $this->getDefaultPubgDescription(),
                'defaultRating' => 5.0,
                'defaultReviewCount' => 2008,
            ];
        } else {
            $content = $content->toApiArray();
        }

        return response()->json(['success' => true, 'data' => $content]);
    }

    /**
     * Get reviews with pagination
     * GET /api/reviews
     */
    public function reviews(Request $request): JsonResponse
    {
        $game = $request->query('game', 'pubg');
        $page = (int) $request->query('page', 1);
        $limit = (int) $request->query('limit', 5);
        $skip = ($page - 1) * $limit;

        $query = Review::where('game', $game)
            ->where('approved', true);

        $total = $query->count();
        $reviews = $query->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($limit)
            ->get();

        // Calculate stats
        $stats = Review::where('game', $game)
            ->where('approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        $avgRating = 5.0;
        $reviewCount = 0;

        if ($stats && $stats->count > 0) {
            $avgRating = round($stats->avg_rating, 1);
            $reviewCount = $stats->count;
        } else {
            // Use defaults from content
            $content = GameContent::find($game);
            if ($content) {
                $avgRating = $content->default_rating ?? 5.0;
                $reviewCount = $content->default_review_count ?? 0;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews->map->toApiArray(),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => ceil($total / $limit),
                    'hasMore' => $skip + $reviews->count() < $total,
                ],
                'stats' => [
                    'avgRating' => (float) $avgRating,
                    'reviewCount' => $reviewCount ?: $total,
                ],
            ],
        ]);
    }

    /**
     * Get legal page by slug
     * GET /api/legal/{slug}
     */
    public function legalPage(string $slug): JsonResponse
    {
        $page = LegalPage::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'error' => 'Sayfa bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page->toApiArray(),
        ]);
    }

    /**
     * Get footer settings
     * GET /api/footer-settings
     */
    public function footerSettings(): JsonResponse
    {
        $settings = DB::table('footer_settings')
            ->where('active', true)
            ->first();

        if (!$settings) {
            // Default with legal pages
            $legalPages = LegalPage::where('is_active', true)
                ->orderBy('order')
                ->get();

            $data = [
                'quickLinks' => [
                    ['label' => 'Giriş Yap', 'action' => 'login'],
                    ['label' => 'Kayıt Ol', 'action' => 'register'],
                ],
                'categories' => [
                    ['label' => 'PUBG Mobile', 'url' => '/'],
                ],
                'corporateLinks' => $legalPages->map(fn($p) => ['label' => $p->title, 'slug' => $p->slug])->toArray(),
            ];
        } else {
            $data = [
                'quickLinks' => json_decode($settings->quick_links, true) ?? [],
                'categories' => json_decode($settings->categories, true) ?? [],
                'corporateLinks' => json_decode($settings->corporate_links, true) ?? [],
            ];
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get SEO settings
     * GET /api/seo/settings
     */
    public function seoSettings(): JsonResponse
    {
        $settings = DB::table('seo_settings')
            ->where('active', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'ga4MeasurementId' => ($settings?->enable_analytics ?? false) ? $settings->ga4_measurement_id : null,
                'gscVerificationCode' => ($settings?->enable_search_console ?? false) ? $settings->gsc_verification_code : null,
            ],
        ]);
    }

    private function getDefaultPubgDescription(): string
    {
        return "# PUBG Mobile UC Satın Al\n\nPUBG Mobile, dünyanın en popüler battle royale oyunlarından biridir...";
    }
}