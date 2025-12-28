<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FooterController extends Controller
{
    /**
     * Get footer settings
     * GET /api/admin/footer-settings
     */
    public function getFooterSettings(Request $request): JsonResponse
    {
        $settings = DB::table('footer_settings')
            ->where('active', true)
            ->first();

        if (!$settings) {
            // Get legal pages for default corporate links
            $legalPages = DB::table('legal_pages')
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'quickLinks' => [
                        ['label' => 'Giriş Yap', 'action' => 'login'],
                        ['label' => 'Kayıt Ol', 'action' => 'register'],
                    ],
                    'categories' => [
                        ['label' => 'PUBG Mobile', 'url' => '/'],
                    ],
                    'corporateLinks' => $legalPages->map(fn($p) => [
                        'label' => $p->title,
                        'slug' => $p->slug,
                    ])->toArray(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'quickLinks' => json_decode($settings->quick_links, true) ?? [],
                'categories' => json_decode($settings->categories, true) ?? [],
                'corporateLinks' => json_decode($settings->corporate_links, true) ?? [],
            ],
        ]);
    }

    /**
     * Update footer settings
     * POST /api/admin/footer-settings
     */
    public function updateFooterSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quickLinks' => 'sometimes|array',
            'categories' => 'sometimes|array',
            'corporateLinks' => 'sometimes|array',
        ]);

        $existing = DB::table('footer_settings')->where('active', true)->first();

        $updateData = [
            'quick_links' => json_encode($data['quickLinks'] ?? []),
            'categories' => json_encode($data['categories'] ?? []),
            'corporate_links' => json_encode($data['corporateLinks'] ?? []),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('footer_settings')
                ->where('id', $existing->id)
                ->update($updateData);
        } else {
            $updateData['id'] = Uuid::uuid4()->toString();
            $updateData['active'] = true;
            $updateData['created_at'] = now();
            DB::table('footer_settings')->insert($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Footer ayarları güncellendi',
        ]);
    }
}