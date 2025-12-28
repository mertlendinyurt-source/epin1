<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ProductController extends Controller
{
    private AuditService $audit;

    public function __construct(AuditService $audit)
    {
        $this->audit = $audit;
    }

    /**
     * Get all products (including inactive)
     * GET /api/admin/products
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $products->map->toApiArray(),
        ]);
    }

    /**
     * Create product
     * POST /api/admin/products
     */
    public function store(Request $request): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $data = $request->validate([
            'title' => 'required|string|max:100',
            'ucAmount' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'discountPrice' => 'required|numeric|min:0',
            'imageUrl' => 'nullable|string',
            'regionCode' => 'nullable|string|max:10',
            'sortOrder' => 'nullable|integer',
        ]);

        // Auto-calculate discount percent
        $discountPercent = 0;
        if ($data['price'] > 0 && $data['discountPrice'] < $data['price']) {
            $discountPercent = (($data['price'] - $data['discountPrice']) / $data['price']) * 100;
        }

        $product = new Product();
        $product->id = Uuid::uuid4()->toString();
        $product->title = $data['title'];
        $product->uc_amount = $data['ucAmount'];
        $product->price = $data['price'];
        $product->discount_price = $data['discountPrice'];
        $product->discount_percent = round($discountPercent, 2);
        $product->image_url = $data['imageUrl'] ?? null;
        $product->region_code = $data['regionCode'] ?? 'TR';
        $product->sort_order = $data['sortOrder'] ?? 0;
        $product->active = true;
        $product->save();

        $this->audit->logFromRequest(
            AuditService::PRODUCT_CREATE,
            $authAdmin['id'] ?? null,
            'product',
            $product->id,
            ['title' => $product->title]
        );

        return response()->json([
            'success' => true,
            'data' => $product->toApiArray(),
        ], 201);
    }

    /**
     * Update product
     * PUT /api/admin/products/{productId}
     */
    public function update(Request $request, string $productId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Ürün bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:100',
            'ucAmount' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'discountPrice' => 'sometimes|numeric|min:0',
            'imageUrl' => 'nullable|string',
            'regionCode' => 'nullable|string|max:10',
            'sortOrder' => 'sometimes|integer',
            'active' => 'sometimes|boolean',
        ]);

        if (isset($data['title'])) $product->title = $data['title'];
        if (isset($data['ucAmount'])) $product->uc_amount = $data['ucAmount'];
        if (isset($data['price'])) $product->price = $data['price'];
        if (isset($data['discountPrice'])) $product->discount_price = $data['discountPrice'];
        if (isset($data['imageUrl'])) $product->image_url = $data['imageUrl'];
        if (isset($data['regionCode'])) $product->region_code = $data['regionCode'];
        if (isset($data['sortOrder'])) $product->sort_order = $data['sortOrder'];
        if (isset($data['active'])) $product->active = $data['active'];

        // Recalculate discount percent
        if ($product->price > 0 && $product->discount_price < $product->price) {
            $product->discount_percent = round((($product->price - $product->discount_price) / $product->price) * 100, 2);
        } else {
            $product->discount_percent = 0;
        }

        $product->save();

        $this->audit->logFromRequest(
            AuditService::PRODUCT_UPDATE,
            $authAdmin['id'] ?? null,
            'product',
            $product->id,
            ['changes' => $data]
        );

        return response()->json([
            'success' => true,
            'data' => $product->toApiArray(),
        ]);
    }

    /**
     * Delete product (PERMANENT)
     * DELETE /api/admin/products/{productId}
     */
    public function destroy(Request $request, string $productId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Ürün bulunamadı',
            ], 404);
        }

        // Check for pending orders
        $pendingOrders = DB::table('orders')
            ->where('product_id', $productId)
            ->where('status', 'pending')
            ->count();

        if ($pendingOrders > 0) {
            return response()->json([
                'success' => false,
                'error' => 'Bu ürüne ait bekleyen siparişler var. Önce siparişleri tamamlayın.',
            ], 400);
        }

        $title = $product->title;

        // Delete associated stocks
        Stock::where('product_id', $productId)->delete();

        // Delete product
        $product->delete();

        $this->audit->logFromRequest(
            AuditService::PRODUCT_DELETE,
            $authAdmin['id'] ?? null,
            'product',
            $productId,
            ['title' => $title]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ürün başarıyla silindi',
        ]);
    }

    /**
     * Get product stock
     * GET /api/admin/products/{productId}/stock
     */
    public function getStock(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Ürün bulunamadı',
            ], 404);
        }

        $stocks = Stock::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        $available = $stocks->where('status', 'available')->count();
        $assigned = $stocks->where('status', 'assigned')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'stocks' => $stocks->map->toApiArray(),
                'summary' => [
                    'total' => $stocks->count(),
                    'available' => $available,
                    'assigned' => $assigned,
                ],
            ],
        ]);
    }

    /**
     * Add stock (bulk)
     * POST /api/admin/products/{productId}/stock
     */
    public function addStock(Request $request, string $productId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Ürün bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'codes' => 'required|array|min:1',
            'codes.*' => 'required|string|min:1',
        ]);

        $added = 0;
        $duplicates = 0;

        foreach ($data['codes'] as $code) {
            $code = trim($code);
            if (empty($code)) continue;

            // Check for duplicates
            $exists = Stock::where('product_id', $productId)
                ->where('value', $code)
                ->exists();

            if ($exists) {
                $duplicates++;
                continue;
            }

            $stock = new Stock();
            $stock->id = Uuid::uuid4()->toString();
            $stock->product_id = $productId;
            $stock->value = $code;
            $stock->status = 'available';
            $stock->created_by = $authAdmin['username'] ?? null;
            $stock->save();
            $added++;
        }

        $this->audit->logFromRequest(
            AuditService::STOCK_ADD,
            $authAdmin['id'] ?? null,
            'product',
            $productId,
            ['added' => $added, 'duplicates' => $duplicates]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'added' => $added,
                'duplicates' => $duplicates,
            ],
            'message' => "{$added} stok eklendi" . ($duplicates > 0 ? ", {$duplicates} kopya atlandı" : ''),
        ]);
    }
}