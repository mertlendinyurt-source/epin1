<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use App\Models\Region;
use App\Models\Review;
use App\Models\GameContent;
use App\Services\AuditService;
use App\Services\CryptoService;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class SettingsController extends Controller
{
    private AuditService $audit;
    private CryptoService $crypto;
    private EmailService $email;

    public function __construct(AuditService $audit, CryptoService $crypto, EmailService $email)
    {
        $this->audit = $audit;
        $this->crypto = $crypto;
        $this->email = $email;
    }

    // ==========================================
    // SITE SETTINGS
    // ==========================================

    /**
     * Get site settings
     * GET /api/admin/settings/site
     */
    public function getSiteSettings(Request $request): JsonResponse
    {
        $settings = SiteSettings::getActive();

        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => [
                    'logo' => null,
                    'favicon' => null,
                    'heroImage' => null,
                    'categoryIcon' => null,
                    'siteName' => 'PINLY',
                    'metaTitle' => 'PINLY – Dijital Kod ve Oyun Satış Platformu',
                    'metaDescription' => 'PUBG Mobile UC satın al. Güvenilir, hızlı ve uygun fiyatlı UC satış platformu.',
                    'contactEmail' => '',
                    'contactPhone' => '',
                    'dailyBannerEnabled' => true,
                    'dailyBannerTitle' => 'Bugüne Özel Fiyatlar',
                    'dailyBannerSubtitle' => '',
                    'dailyBannerIcon' => 'fire',
                    'dailyCountdownEnabled' => true,
                    'dailyCountdownLabel' => 'Kampanya bitimine',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings->toApiArray(),
        ]);
    }

    /**
     * Update site settings
     * POST /api/admin/settings/site
     */
    public function updateSiteSettings(Request $request): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $data = $request->validate([
            'siteName' => 'sometimes|string|max:100',
            'metaTitle' => 'sometimes|string|max:200',
            'metaDescription' => 'sometimes|string|max:500',
            'contactEmail' => 'sometimes|nullable|email',
            'contactPhone' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|string',
            'favicon' => 'sometimes|nullable|string',
            'heroImage' => 'sometimes|nullable|string',
            'categoryIcon' => 'sometimes|nullable|string',
            'dailyBannerEnabled' => 'sometimes|boolean',
            'dailyBannerTitle' => 'sometimes|nullable|string|max:100',
            'dailyBannerSubtitle' => 'sometimes|nullable|string|max:200',
            'dailyBannerIcon' => 'sometimes|nullable|string|max:50',
            'dailyCountdownEnabled' => 'sometimes|boolean',
            'dailyCountdownLabel' => 'sometimes|nullable|string|max:100',
        ]);

        $settings = SiteSettings::getActive();

        if (!$settings) {
            $settings = new SiteSettings();
            $settings->id = Uuid::uuid4()->toString();
            $settings->active = true;
        }

        if (isset($data['siteName'])) $settings->site_name = $data['siteName'];
        if (isset($data['metaTitle'])) $settings->meta_title = $data['metaTitle'];
        if (isset($data['metaDescription'])) $settings->meta_description = $data['metaDescription'];
        if (isset($data['contactEmail'])) $settings->contact_email = $data['contactEmail'];
        if (isset($data['contactPhone'])) $settings->contact_phone = $data['contactPhone'];
        if (isset($data['logo'])) $settings->logo = $data['logo'];
        if (isset($data['favicon'])) $settings->favicon = $data['favicon'];
        if (isset($data['heroImage'])) $settings->hero_image = $data['heroImage'];
        if (isset($data['categoryIcon'])) $settings->category_icon = $data['categoryIcon'];
        if (isset($data['dailyBannerEnabled'])) $settings->daily_banner_enabled = $data['dailyBannerEnabled'];
        if (isset($data['dailyBannerTitle'])) $settings->daily_banner_title = $data['dailyBannerTitle'];
        if (isset($data['dailyBannerSubtitle'])) $settings->daily_banner_subtitle = $data['dailyBannerSubtitle'];
        if (isset($data['dailyBannerIcon'])) $settings->daily_banner_icon = $data['dailyBannerIcon'];
        if (isset($data['dailyCountdownEnabled'])) $settings->daily_countdown_enabled = $data['dailyCountdownEnabled'];
        if (isset($data['dailyCountdownLabel'])) $settings->daily_countdown_label = $data['dailyCountdownLabel'];

        $settings->save();

        $this->audit->logFromRequest(
            AuditService::SITE_SETTINGS_UPDATE,
            $authAdmin['id'] ?? null,
            'settings',
            'site'
        );

        return response()->json([
            'success' => true,
            'data' => $settings->toApiArray(),
        ]);
    }

    // ==========================================
    // PAYMENT SETTINGS (SHOPIER)
    // ==========================================

    /**
     * Get Shopier settings (masked)
     * GET /api/admin/settings/payments
     */
    public function getPaymentSettings(Request $request): JsonResponse
    {
        $settings = DB::table('shopier_settings')
            ->where('is_active', true)
            ->first();

        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => [
                    'isConfigured' => false,
                    'apiKey' => null,
                    'mode' => 'production',
                    'message' => 'Shopier ayarları henüz yapılmadı',
                ],
            ]);
        }

        $apiKeyMasked = null;
        if ($settings->api_key) {
            $decrypted = $this->crypto->decrypt($settings->api_key);
            $apiKeyMasked = $this->crypto->maskSensitiveData($decrypted);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'isConfigured' => true,
                'apiKey' => $apiKeyMasked,
                'mode' => $settings->mode ?? 'production',
                'updatedBy' => $settings->updated_by,
                'updatedAt' => $settings->updated_at,
            ],
        ]);
    }

    /**
     * Update Shopier settings
     * POST /api/admin/settings/payments
     */
    public function updatePaymentSettings(Request $request): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $data = $request->validate([
            'apiKey' => 'required|string|min:10',
            'apiSecret' => 'required|string|min:10',
            'mode' => 'sometimes|in:production,test',
        ]);

        // Encrypt sensitive data
        $encryptedKey = $this->crypto->encrypt($data['apiKey']);
        $encryptedSecret = $this->crypto->encrypt($data['apiSecret']);

        // Update or create
        $existing = DB::table('shopier_settings')->where('is_active', true)->first();

        if ($existing) {
            DB::table('shopier_settings')
                ->where('id', $existing->id)
                ->update([
                    'api_key' => $encryptedKey,
                    'api_secret' => $encryptedSecret,
                    'mode' => $data['mode'] ?? 'production',
                    'updated_by' => $authAdmin['username'] ?? null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('shopier_settings')->insert([
                'id' => Uuid::uuid4()->toString(),
                'api_key' => $encryptedKey,
                'api_secret' => $encryptedSecret,
                'mode' => $data['mode'] ?? 'production',
                'is_active' => true,
                'updated_by' => $authAdmin['username'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->audit->logFromRequest(
            AuditService::PAYMENT_SETTINGS_UPDATE,
            $authAdmin['id'] ?? null,
            'settings',
            'payment'
        );

        return response()->json([
            'success' => true,
            'message' => 'Shopier ayarları güncellendi',
        ]);
    }

    // ==========================================
    // GOOGLE OAUTH SETTINGS
    // ==========================================

    /**
     * Get Google OAuth settings
     * GET /api/admin/settings/oauth/google
     */
    public function getOAuthSettings(Request $request): JsonResponse
    {
        $settings = DB::table('oauth_settings')
            ->where('provider', 'google')
            ->first();

        $baseUrl = config('app.url');

        $clientIdMasked = null;
        if ($settings?->client_id) {
            $decrypted = $this->crypto->decrypt($settings->client_id);
            $clientIdMasked = $this->crypto->maskSensitiveData($decrypted);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => (bool) ($settings?->enabled ?? false),
                'clientId' => $clientIdMasked,
                'clientSecret' => $settings?->client_secret ? '••••••••' : '',
                'hasClientId' => (bool) $settings?->client_id,
                'hasClientSecret' => (bool) $settings?->client_secret,
                'baseUrl' => $baseUrl,
                'redirectUri' => $baseUrl . '/api/auth/google/callback',
                'authorizedOrigin' => $baseUrl,
                'updatedBy' => $settings?->updated_by,
                'updatedAt' => $settings?->updated_at,
            ],
        ]);
    }

    /**
     * Update Google OAuth settings
     * POST /api/admin/settings/oauth/google
     */
    public function updateOAuthSettings(Request $request): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $data = $request->validate([
            'enabled' => 'required|boolean',
            'clientId' => 'sometimes|nullable|string',
            'clientSecret' => 'sometimes|nullable|string',
        ]);

        $existing = DB::table('oauth_settings')
            ->where('provider', 'google')
            ->first();

        $updateData = [
            'enabled' => $data['enabled'],
            'updated_by' => $authAdmin['username'] ?? null,
            'updated_at' => now(),
        ];

        // Only update credentials if provided (not masked value)
        if (!empty($data['clientId']) && !str_contains($data['clientId'], '*')) {
            $updateData['client_id'] = $this->crypto->encrypt($data['clientId']);
        }
        if (!empty($data['clientSecret']) && !str_contains($data['clientSecret'], '•')) {
            $updateData['client_secret'] = $this->crypto->encrypt($data['clientSecret']);
        }

        if ($existing) {
            DB::table('oauth_settings')
                ->where('id', $existing->id)
                ->update($updateData);
        } else {
            $updateData['id'] = Uuid::uuid4()->toString();
            $updateData['provider'] = 'google';
            $updateData['created_at'] = now();
            DB::table('oauth_settings')->insert($updateData);
        }

        $this->audit->logFromRequest(
            AuditService::OAUTH_SETTINGS_UPDATE,
            $authAdmin['id'] ?? null,
            'settings',
            'oauth'
        );

        return response()->json([
            'success' => true,
            'message' => 'Google OAuth ayarları güncellendi',
        ]);
    }

    // ==========================================
    // EMAIL SETTINGS
    // ==========================================

    /**
     * Get email settings
     * GET /api/admin/email/settings
     */
    public function getEmailSettings(Request $request): JsonResponse
    {
        $settings = DB::table('email_settings')
            ->where('id', 'main')
            ->first();

        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => [
                    'enableEmail' => false,
                    'fromName' => '',
                    'fromEmail' => '',
                    'smtpHost' => '',
                    'smtpPort' => '587',
                    'smtpSecure' => false,
                    'smtpUser' => '',
                    'smtpPass' => '',
                    'testRecipientEmail' => '',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enableEmail' => (bool) $settings->enable_email,
                'fromName' => $settings->from_name,
                'fromEmail' => $settings->from_email,
                'smtpHost' => $settings->smtp_host,
                'smtpPort' => $settings->smtp_port,
                'smtpSecure' => (bool) $settings->smtp_secure,
                'smtpUser' => $settings->smtp_user,
                'smtpPass' => $settings->smtp_pass ? '••••••••' : '',
                'testRecipientEmail' => $settings->test_recipient_email,
            ],
        ]);
    }

    /**
     * Update email settings
     * POST /api/admin/email/settings
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $data = $request->validate([
            'enableEmail' => 'required|boolean',
            'fromName' => 'sometimes|nullable|string',
            'fromEmail' => 'sometimes|nullable|email',
            'smtpHost' => 'sometimes|nullable|string',
            'smtpPort' => 'sometimes|nullable|string',
            'smtpSecure' => 'sometimes|boolean',
            'smtpUser' => 'sometimes|nullable|string',
            'smtpPass' => 'sometimes|nullable|string',
            'testRecipientEmail' => 'sometimes|nullable|email',
        ]);

        $existing = DB::table('email_settings')->where('id', 'main')->first();

        $updateData = [
            'enable_email' => $data['enableEmail'],
            'from_name' => $data['fromName'] ?? null,
            'from_email' => $data['fromEmail'] ?? null,
            'smtp_host' => $data['smtpHost'] ?? null,
            'smtp_port' => $data['smtpPort'] ?? '587',
            'smtp_secure' => $data['smtpSecure'] ?? false,
            'smtp_user' => $data['smtpUser'] ?? null,
            'test_recipient_email' => $data['testRecipientEmail'] ?? null,
            'updated_at' => now(),
        ];

        // Only update password if not masked
        if (!empty($data['smtpPass']) && !str_contains($data['smtpPass'], '•')) {
            $updateData['smtp_pass'] = $this->crypto->encrypt($data['smtpPass']);
        }

        if ($existing) {
            DB::table('email_settings')
                ->where('id', 'main')
                ->update($updateData);
        } else {
            $updateData['id'] = 'main';
            $updateData['created_at'] = now();
            DB::table('email_settings')->insert($updateData);
        }

        $this->audit->logFromRequest(
            AuditService::EMAIL_SETTINGS_UPDATE,
            $authAdmin['id'] ?? null,
            'settings',
            'email'
        );

        return response()->json([
            'success' => true,
            'message' => 'E-posta ayarları güncellendi',
        ]);
    }

    /**
     * Test email
     * POST /api/admin/email/test
     */
    public function testEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipientEmail' => 'required|email',
        ]);

        $content = [
            'subject' => 'PINLY Test E-postası',
            'title' => 'Test E-postası ✅',
            'body' => '<p>Bu bir test e-postasıdır.</p><p>E-posta ayarlarınız doğru yapılandırılmış.</p>',
            'info' => 'SMTP bağlantısı başarılı.',
        ];

        $html = $this->email->generateTemplate($content);
        $result = $this->email->send(
            $data['recipientEmail'],
            $content['subject'],
            $html,
            'test',
            'system',
            null,
            null,
            true // Skip duplicate check
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Test e-postası gönderildi',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'E-posta gönderilemedi: ' . ($result['error'] ?? $result['reason']),
            ], 500);
        }
    }

    /**
     * Get email logs
     * GET /api/admin/email/logs
     */
    public function getEmailLogs(Request $request): JsonResponse
    {
        $logs = DB::table('email_logs')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs->map(fn($log) => [
                'id' => $log->id,
                'type' => $log->type,
                'userId' => $log->user_id,
                'orderId' => $log->order_id,
                'ticketId' => $log->ticket_id,
                'to' => $log->to,
                'status' => $log->status,
                'error' => $log->error,
                'createdAt' => $log->created_at,
            ]),
        ]);
    }

    // ==========================================
    // LEGAL PAGES
    // ==========================================

    /**
     * Get all legal pages
     * GET /api/admin/legal-pages
     */
    public function getLegalPages(Request $request): JsonResponse
    {
        $pages = LegalPage::orderBy('order')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $pages->map->toApiArray(),
        ]);
    }

    /**
     * Create legal page
     * POST /api/admin/legal-pages
     */
    public function createLegalPage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'required|string|max:100|unique:legal_pages,slug',
            'content' => 'required|string',
            'isActive' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        $page = new LegalPage();
        $page->id = Uuid::uuid4()->toString();
        $page->title = $data['title'];
        $page->slug = $data['slug'];
        $page->content = $data['content'];
        $page->is_active = $data['isActive'] ?? true;
        $page->order = $data['order'] ?? 0;
        $page->save();

        return response()->json([
            'success' => true,
            'data' => $page->toApiArray(),
        ], 201);
    }

    /**
     * Update legal page
     * PUT /api/admin/legal-pages/{pageId}
     */
    public function updateLegalPage(Request $request, string $pageId): JsonResponse
    {
        $page = LegalPage::find($pageId);
        if (!$page) {
            return response()->json([
                'success' => false,
                'error' => 'Sayfa bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'slug' => 'sometimes|string|max:100|unique:legal_pages,slug,' . $pageId,
            'content' => 'sometimes|string',
            'isActive' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        if (isset($data['title'])) $page->title = $data['title'];
        if (isset($data['slug'])) $page->slug = $data['slug'];
        if (isset($data['content'])) $page->content = $data['content'];
        if (isset($data['isActive'])) $page->is_active = $data['isActive'];
        if (isset($data['order'])) $page->order = $data['order'];

        $page->save();

        return response()->json([
            'success' => true,
            'data' => $page->toApiArray(),
        ]);
    }

    /**
     * Delete legal page
     * DELETE /api/admin/legal-pages/{pageId}
     */
    public function deleteLegalPage(Request $request, string $pageId): JsonResponse
    {
        $page = LegalPage::find($pageId);
        if (!$page) {
            return response()->json([
                'success' => false,
                'error' => 'Sayfa bulunamadı',
            ], 404);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sayfa silindi',
        ]);
    }

    // ==========================================
    // REGIONS
    // ==========================================

    /**
     * Get all regions
     * GET /api/admin/settings/regions
     */
    public function getRegions(Request $request): JsonResponse
    {
        $regions = Region::orderBy('sort_order')->get();

        // Initialize defaults if empty
        if ($regions->isEmpty()) {
            $defaults = [
                ['code' => 'TR', 'name' => 'Türkiye', 'enabled' => true, 'sort_order' => 1],
                ['code' => 'GLOBAL', 'name' => 'Küresel', 'enabled' => true, 'sort_order' => 2],
                ['code' => 'DE', 'name' => 'Almanya', 'enabled' => true, 'sort_order' => 3],
                ['code' => 'FR', 'name' => 'Fransa', 'enabled' => true, 'sort_order' => 4],
                ['code' => 'JP', 'name' => 'Japonya', 'enabled' => true, 'sort_order' => 5],
            ];

            foreach ($defaults as $default) {
                $region = new Region();
                $region->id = Uuid::uuid4()->toString();
                $region->code = $default['code'];
                $region->name = $default['name'];
                $region->enabled = $default['enabled'];
                $region->sort_order = $default['sort_order'];
                $region->save();
            }

            $regions = Region::orderBy('sort_order')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $regions->map->toApiArray(),
        ]);
    }

    /**
     * Update regions
     * PUT /api/admin/settings/regions
     */
    public function updateRegions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'regions' => 'required|array',
            'regions.*.id' => 'required|string',
            'regions.*.code' => 'required|string|max:10',
            'regions.*.name' => 'required|string|max:100',
            'regions.*.enabled' => 'required|boolean',
            'regions.*.flagImageUrl' => 'nullable|string',
            'regions.*.sortOrder' => 'required|integer',
        ]);

        foreach ($data['regions'] as $regionData) {
            $region = Region::find($regionData['id']);
            if ($region) {
                $region->code = $regionData['code'];
                $region->name = $regionData['name'];
                $region->enabled = $regionData['enabled'];
                $region->flag_image_url = $regionData['flagImageUrl'] ?? null;
                $region->sort_order = $regionData['sortOrder'];
                $region->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bölgeler güncellendi',
        ]);
    }

    // ==========================================
    // REVIEWS & CONTENT
    // ==========================================

    /**
     * Get all reviews
     * GET /api/admin/reviews
     */
    public function getReviews(Request $request): JsonResponse
    {
        $game = $request->query('game', 'pubg');
        $reviews = Review::where('game', $game)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews->map->toApiArray(),
        ]);
    }

    /**
     * Create review
     * POST /api/admin/reviews
     */
    public function createReview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'game' => 'sometimes|string',
            'userName' => 'required|string|max:100',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000',
            'approved' => 'sometimes|boolean',
        ]);

        $review = new Review();
        $review->id = Uuid::uuid4()->toString();
        $review->game = $data['game'] ?? 'pubg';
        $review->user_name = $data['userName'];
        $review->rating = $data['rating'];
        $review->comment = $data['comment'] ?? null;
        $review->approved = $data['approved'] ?? true;
        $review->save();

        return response()->json([
            'success' => true,
            'data' => $review->toApiArray(),
        ], 201);
    }

    /**
     * Update review
     * PUT /api/admin/reviews/{reviewId}
     */
    public function updateReview(Request $request, string $reviewId): JsonResponse
    {
        $review = Review::find($reviewId);
        if (!$review) {
            return response()->json([
                'success' => false,
                'error' => 'Değerlendirme bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'userName' => 'sometimes|string|max:100',
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000',
            'approved' => 'sometimes|boolean',
        ]);

        if (isset($data['userName'])) $review->user_name = $data['userName'];
        if (isset($data['rating'])) $review->rating = $data['rating'];
        if (isset($data['comment'])) $review->comment = $data['comment'];
        if (isset($data['approved'])) $review->approved = $data['approved'];

        $review->save();

        return response()->json([
            'success' => true,
            'data' => $review->toApiArray(),
        ]);
    }

    /**
     * Delete review
     * DELETE /api/admin/reviews/{reviewId}
     */
    public function deleteReview(Request $request, string $reviewId): JsonResponse
    {
        $review = Review::find($reviewId);
        if (!$review) {
            return response()->json([
                'success' => false,
                'error' => 'Değerlendirme bulunamadı',
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Değerlendirme silindi',
        ]);
    }

    /**
     * Get game content
     * GET /api/admin/content/pubg
     */
    public function getGameContent(Request $request): JsonResponse
    {
        $content = GameContent::find('pubg');

        if (!$content) {
            return response()->json([
                'success' => true,
                'data' => [
                    'game' => 'pubg',
                    'title' => 'PUBG Mobile',
                    'description' => '',
                    'defaultRating' => 5.0,
                    'defaultReviewCount' => 2008,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $content->toApiArray(),
        ]);
    }

    /**
     * Update game content
     * POST /api/admin/content/pubg
     */
    public function updateGameContent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string',
            'defaultRating' => 'sometimes|numeric|min:0|max:5',
            'defaultReviewCount' => 'sometimes|integer|min:0',
        ]);

        $content = GameContent::find('pubg');

        if (!$content) {
            $content = new GameContent();
            $content->game = 'pubg';
        }

        if (isset($data['title'])) $content->title = $data['title'];
        if (isset($data['description'])) $content->description = $data['description'];
        if (isset($data['defaultRating'])) $content->default_rating = $data['defaultRating'];
        if (isset($data['defaultReviewCount'])) $content->default_review_count = $data['defaultReviewCount'];

        $content->save();

        return response()->json([
            'success' => true,
            'data' => $content->toApiArray(),
        ]);
    }

    // ==========================================
    // SEO SETTINGS
    // ==========================================

    /**
     * Get SEO settings
     * GET /api/admin/settings/seo
     */
    public function getSeoSettings(Request $request): JsonResponse
    {
        $settings = DB::table('seo_settings')
            ->where('active', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $settings ? [
                'ga4MeasurementId' => $settings->ga4_measurement_id,
                'gscVerificationCode' => $settings->gsc_verification_code,
                'enableAnalytics' => (bool) $settings->enable_analytics,
                'enableSearchConsole' => (bool) $settings->enable_search_console,
                'updatedBy' => $settings->updated_by,
                'updatedAt' => $settings->updated_at,
            ] : null,
        ]);
    }

    /**
     * Update SEO settings
     * POST /api/admin/settings/seo
     */
    public function updateSeoSettings(Request $request): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $data = $request->validate([
            'ga4MeasurementId' => 'nullable|string|max:50',
            'gscVerificationCode' => 'nullable|string|max:100',
            'enableAnalytics' => 'required|boolean',
            'enableSearchConsole' => 'required|boolean',
        ]);

        $existing = DB::table('seo_settings')->where('active', true)->first();

        $updateData = [
            'ga4_measurement_id' => $data['ga4MeasurementId'],
            'gsc_verification_code' => $data['gscVerificationCode'],
            'enable_analytics' => $data['enableAnalytics'],
            'enable_search_console' => $data['enableSearchConsole'],
            'updated_by' => $authAdmin['username'] ?? null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('seo_settings')
                ->where('id', $existing->id)
                ->update($updateData);
        } else {
            $updateData['id'] = Uuid::uuid4()->toString();
            $updateData['active'] = true;
            $updateData['created_at'] = now();
            DB::table('seo_settings')->insert($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'SEO ayarları güncellendi',
        ]);
    }
}