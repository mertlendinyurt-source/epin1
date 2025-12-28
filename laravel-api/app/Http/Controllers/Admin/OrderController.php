<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private AuditService $audit;
    private EmailService $email;

    public function __construct(AuditService $audit, EmailService $email)
    {
        $this->audit = $audit;
        $this->email = $email;
    }

    /**
     * Get all orders
     * GET /api/admin/orders
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $riskStatus = $request->query('riskStatus');
        $deliveryStatus = $request->query('deliveryStatus');

        $query = Order::query();

        if ($status) $query->where('status', $status);
        if ($riskStatus) $query->whereRaw("JSON_EXTRACT(risk, '$.status') = ?", [$riskStatus]);
        if ($deliveryStatus) $query->whereRaw("JSON_EXTRACT(delivery, '$.status') = ?", [$deliveryStatus]);

        $orders = $query->orderBy('created_at', 'desc')->get();

        // Flagged count for badge
        $flaggedCount = Order::whereRaw("JSON_EXTRACT(risk, '$.status') = 'FLAGGED'")
            ->whereRaw("JSON_EXTRACT(delivery, '$.status') = 'hold'")
            ->count();

        return response()->json([
            'success' => true,
            'data' => $orders->map->toApiArray(),
            'meta' => ['flaggedCount' => $flaggedCount],
        ]);
    }

    /**
     * Get single order
     * GET /api/admin/orders/{orderId}
     */
    public function show(Request $request, string $orderId): JsonResponse
    {
        $order = Order::find($orderId);
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
                'order' => $order->toApiArray(),
                'payment' => $payment?->toApiArray(),
            ],
        ]);
    }

    /**
     * Approve flagged order
     * POST /api/admin/orders/{orderId}/approve
     */
    public function approve(Request $request, string $orderId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş bulunamadı',
            ], 404);
        }

        // Check if order is flagged and on hold
        $delivery = $order->delivery ?? [];
        if (($delivery['status'] ?? '') !== 'hold') {
            return response()->json([
                'success' => false,
                'error' => 'Bu sipariş onay beklemesinde değil',
            ], 400);
        }

        // Assign stock if paid
        if ($order->status === 'paid') {
            // Find available stock (FIFO)
            $stock = Stock::where('product_id', $order->product_id)
                ->where('status', 'available')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->status = 'assigned';
                $stock->order_id = $order->id;
                $stock->assigned_at = now();
                $stock->save();

                $order->delivery = [
                    'status' => 'delivered',
                    'items' => [$stock->value],
                    'message' => 'Manuel onay ile teslim edildi',
                    'approvedBy' => $authAdmin['username'] ?? null,
                    'deliveredAt' => now()->toISOString(),
                ];

                // Send email
                $user = User::find($order->user_id);
                $product = Product::find($order->product_id);
                if ($user && $product) {
                    try {
                        $this->email->sendDelivered(
                            $order->toArray(),
                            $user->toArray(),
                            $product->toArray(),
                            [$stock->value]
                        );
                    } catch (\Exception $e) {}
                }

            } else {
                $order->delivery = [
                    'status' => 'pending',
                    'items' => [],
                    'message' => 'Stok bekleniyor',
                    'approvedBy' => $authAdmin['username'] ?? null,
                ];
            }
        }

        $order->save();

        $this->audit->logFromRequest(
            AuditService::ORDER_MANUAL_APPROVE,
            $authAdmin['id'] ?? null,
            'order',
            $orderId
        );

        return response()->json([
            'success' => true,
            'data' => $order->toApiArray(),
            'message' => 'Sipariş onaylandı',
        ]);
    }

    /**
     * Refund order
     * POST /api/admin/orders/{orderId}/refund
     */
    public function refund(Request $request, string $orderId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'reason' => 'sometimes|string|max:500',
        ]);

        // Release assigned stock back to available
        $assignedStocks = Stock::where('order_id', $orderId)->get();
        foreach ($assignedStocks as $stock) {
            $stock->status = 'available';
            $stock->order_id = null;
            $stock->assigned_at = null;
            $stock->save();
        }

        $order->status = 'refunded';
        $order->delivery = [
            'status' => 'cancelled',
            'items' => [],
            'message' => $data['reason'] ?? 'İade edildi',
            'refundedBy' => $authAdmin['username'] ?? null,
            'refundedAt' => now()->toISOString(),
        ];
        $order->save();

        $this->audit->logFromRequest(
            AuditService::ORDER_MANUAL_REFUND,
            $authAdmin['id'] ?? null,
            'order',
            $orderId,
            ['reason' => $data['reason'] ?? '']
        );

        return response()->json([
            'success' => true,
            'data' => $order->toApiArray(),
            'message' => 'Sipariş iade edildi',
        ]);
    }

    /**
     * Update order delivery status
     * PUT /api/admin/orders/{orderId}/delivery
     */
    public function updateDelivery(Request $request, string $orderId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş bulunamadı',
            ], 404);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,delivered,hold,cancelled',
            'message' => 'sometimes|string|max:500',
        ]);

        $delivery = $order->delivery ?? [];
        $delivery['status'] = $data['status'];
        if (isset($data['message'])) {
            $delivery['message'] = $data['message'];
        }
        $delivery['updatedBy'] = $authAdmin['username'] ?? null;
        $delivery['updatedAt'] = now()->toISOString();

        $order->delivery = $delivery;
        $order->save();

        $this->audit->logFromRequest(
            AuditService::ORDER_STATUS_CHANGE,
            $authAdmin['id'] ?? null,
            'order',
            $orderId,
            ['deliveryStatus' => $data['status']]
        );

        return response()->json([
            'success' => true,
            'data' => $order->toApiArray(),
        ]);
    }
}