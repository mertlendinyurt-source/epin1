<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard stats
     * GET /api/admin/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $totalOrders = Order::count();
        $paidOrders = Order::where('status', 'paid')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        
        $totalRevenue = Order::where('status', 'paid')->sum('amount');

        $recentOrders = Order::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'totalOrders' => $totalOrders,
                    'paidOrders' => $paidOrders,
                    'pendingOrders' => $pendingOrders,
                    'totalRevenue' => (float) $totalRevenue,
                ],
                'recentOrders' => $recentOrders->map->toApiArray(),
            ],
        ]);
    }

    /**
     * Get system status
     * GET /api/admin/system-status
     */
    public function systemStatus(Request $request): JsonResponse
    {
        $usersCount = User::count();
        $ordersToday = Order::whereDate('created_at', today())->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $availableStock = DB::table('stocks')->where('status', 'available')->count();
        $openTickets = DB::table('tickets')->where('status', '!=', 'closed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'version' => '1.0.0',
                'uptime' => 0,
                'timestamp' => now()->toISOString(),
                'metrics' => [
                    'totalUsers' => $usersCount,
                    'ordersToday' => $ordersToday,
                    'pendingOrders' => $pendingOrders,
                    'availableStock' => $availableStock,
                    'openTickets' => $openTickets,
                ],
                'status' => 'healthy',
            ],
        ]);
    }

    /**
     * Get audit logs
     * GET /api/admin/audit-logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $limit = (int) $request->query('limit', 50);
        $action = $request->query('action');
        $entityType = $request->query('entityType');
        $actorId = $request->query('actorId');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $query = DB::table('audit_logs');

        if ($action) $query->where('action', $action);
        if ($entityType) $query->where('entity_type', $entityType);
        if ($actorId) $query->where('actor_id', $actorId);
        if ($startDate) $query->where('created_at', '>=', $startDate);
        if ($endDate) $query->where('created_at', '<=', $endDate);

        $total = $query->count();
        $logs = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $actionTypes = DB::table('audit_logs')->distinct()->pluck('action');
        $entityTypes = DB::table('audit_logs')->distinct()->pluck('entity_type');

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $logs->map(fn($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actorId' => $log->actor_id,
                    'entityType' => $log->entity_type,
                    'entityId' => $log->entity_id,
                    'ip' => $log->ip,
                    'userAgent' => $log->user_agent,
                    'meta' => json_decode($log->meta, true),
                    'createdAt' => $log->created_at,
                ]),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                ],
                'filters' => [
                    'actionTypes' => $actionTypes,
                    'entityTypes' => $entityTypes,
                ],
            ],
        ]);
    }
}