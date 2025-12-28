<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Stock;
use App\Models\Payment;
use App\Services\ShopierService;
use App\Services\RiskService;
use App\Services\EmailService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class OrderController extends Controller
{
    private ShopierService $shopier;
    private RiskService $risk;
    private EmailService $email;
    private AuditService $audit;

    public function __construct(
        ShopierService $shopier,
        RiskService $risk,
        EmailService $email,
        AuditService $audit
    ) {
        $this->shopier = $shopier;
        $this->risk = $risk;
        $this->email = $email;
        $this->audit = $audit;
    }

    /**
     * Create order
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');
        
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş vermek için giriş yapmalısınız',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $data = $request->validate([
            'productId' => 'required|string',
            'playerId' => 'required|string|min:6',
            'playerName' => 'required|string',
        ]);

        // Get product
        $product = Product::where('id', $data['productId'])
            ->where('active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Ürün bulunamadı',
            ], 404);
        }

        // Get user
        $user = User::find($authUser['id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Kullanıcı bulunamadı',
            ], 404);
        }

        // Check Shopier configuration
        if (!$this->shopier->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Ödeme sistemi yapılandırılmamış. Lütfen daha sonra tekrar deneyin.',
            ], 503);
        }

        // Create order
        $order = new Order();
        $order->id = Uuid::uuid4()->toString();
        $order->user_id = $user->id;
        $order->product_id = $product->id;
        $order->product_title = $product->title;
        $order->uc_amount = $product->uc_amount;
        $order->amount = $product->discount_price; // Backend-controlled price
        $order->player_id = $data['playerId'];
        $order->player_name = $data['playerName'];
        $order->status = 'pending';
        $order->customer = [
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
        $order->delivery = [
            'status' => 'pending',
            'items' => [],
            'message' => null,
        ];
        $order->meta = [
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ];

        // Calculate risk
        $riskResult = $this->risk->calculateRisk($order, $user, $request->ip());
        $order->risk = $riskResult;

        // If flagged, set delivery to hold
        if ($riskResult['status'] === 'FLAGGED') {
            $order->delivery = [
                'status' => 'hold',
                'items' => [],
                'message' => 'Sipariş inceleme bekliyor',
            ];

            $this->audit->logFromRequest(
                AuditService::ORDER_RISK_FLAG,
                $user->id,
                'order',
                $order->id,
                ['score' => $riskResult['score'], 'reasons' => $riskResult['reasons']]
            );
        }

        $order->save();

        // Generate payment URL
        try {
            $paymentResult = $this->shopier->createPayment(
                $order->toArray(),
                $product->toArray(),
                $user->toArray()
            );

            $order->payment_url = $paymentResult['paymentUrl'];
            $order->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'orderId' => $order->id,
                    'paymentUrl' => $paymentResult['paymentUrl'],
                    'paymentData' => $paymentResult['paymentData'],
                    'signature' => $paymentResult['signature'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ödeme sayfası oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's orders
     * GET /api/account/orders
     */
    public function userOrders(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $orders = Order::where('user_id', $authUser['id'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map->toUserApiArray(),
        ]);
    }

    /**
     * Get user's recent orders (last 5)
     * GET /api/account/orders/recent
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $orders = Order::where('user_id', $authUser['id'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map->toUserApiArray(),
        ]);
    }

    /**
     * Get single order for user
     * GET /api/account/orders/{orderId}
     */
    public function userOrderDetail(Request $request, string $orderId): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $order = Order::where('id', $orderId)
            ->where('user_id', $authUser['id'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş bulunamadı',
            ], 404);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order->toUserApiArray(),
                'payment' => $payment?->toApiArray(),
            ],
        ]);
    }

    /**
     * Shopier payment callback
     * POST /api/payment/shopier/callback
     */
    public function shopierCallback(Request $request): JsonResponse
    {
        $data = $request->all();

        try {
            $callbackResult = $this->shopier->processCallback($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }

        $orderId = $callbackResult['orderId'];
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş bulunamadı',
            ], 404);
        }

        // Hash validation
        if (!$callbackResult['hashValid']) {
            // Log security event
            DB::table('security_logs')->insert([
                'id' => Uuid::uuid4()->toString(),
                'type' => 'hash_mismatch',
                'order_id' => $orderId,
                'ip' => $request->ip(),
                'details' => json_encode(['received' => $data]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Hash doğrulama başarısız',
            ], 403);
        }

        // Idempotency - already paid
        if ($order->status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Ödeme zaten işlenmiş',
            ]);
        }

        // Check transaction ID uniqueness
        $existingPayment = Payment::where('transaction_id', $callbackResult['transactionId'])->first();
        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'message' => 'İşlem zaten kaydedilmiş',
            ]);
        }

        // Immutable status transitions
        if ($order->status === 'failed' && $callbackResult['status'] === 'success') {
            return response()->json([
                'success' => false,
                'error' => 'Geçersiz durum geçişi',
            ], 400);
        }

        // Process payment
        $newStatus = $callbackResult['status'] === 'success' ? 'paid' : 'failed';

        // Create payment record
        $payment = new Payment();
        $payment->id = Uuid::uuid4()->toString();
        $payment->order_id = $orderId;
        $payment->transaction_id = $callbackResult['transactionId'];
        $payment->status = $newStatus === 'paid' ? 'success' : 'failed';
        $payment->amount = $callbackResult['amount'];
        $payment->hash_validated = true;
        $payment->raw_payload = $callbackResult['rawPayload'];
        $payment->verified_at = now();
        $payment->save();

        // Update order status
        $order->status = $newStatus;
        if ($newStatus === 'paid') {
            $order->paid_at = now();
        }

        // Auto-assign stock if paid and not flagged
        if ($newStatus === 'paid') {
            $delivery = $order->delivery ?? [];
            $deliveryStatus = $delivery['status'] ?? 'pending';

            // Only auto-assign if not on hold (not flagged)
            if ($deliveryStatus !== 'hold') {
                $this->assignStock($order);
            }
        }

        $order->save();

        $this->audit->logFromRequest(
            AuditService::ORDER_STATUS_CHANGE,
            null,
            'order',
            $orderId,
            ['from' => 'pending', 'to' => $newStatus]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ödeme işlendi',
            'data' => ['status' => $newStatus],
        ]);
    }

    /**
     * Assign stock to order (FIFO)
     */
    private function assignStock(Order $order): void
    {
        $user = User::find($order->user_id);
        $product = Product::find($order->product_id);

        // Find available stock (FIFO - oldest first)
        $stock = Stock::where('product_id', $order->product_id)
            ->where('status', 'available')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();

        if ($stock) {
            // Assign stock
            $stock->status = 'assigned';
            $stock->order_id = $order->id;
            $stock->assigned_at = now();
            $stock->save();

            // Update order delivery
            $order->delivery = [
                'status' => 'delivered',
                'items' => [$stock->value],
                'message' => 'Teslim edildi',
                'deliveredAt' => now()->toISOString(),
            ];

            // Send delivery email
            if ($user && $product) {
                try {
                    $this->email->sendDelivered(
                        $order->toArray(),
                        $user->toArray(),
                        $product->toArray(),
                        [$stock->value]
                    );
                } catch (\Exception $e) {
                    // Log but don't fail
                }
            }

            $this->audit->logFromRequest(
                AuditService::STOCK_ASSIGN,
                null,
                'stock',
                $stock->id,
                ['orderId' => $order->id]
            );

        } else {
            // No stock available
            $order->delivery = [
                'status' => 'pending',
                'items' => [],
                'message' => 'Stok bekleniyor',
            ];
        }
    }
}