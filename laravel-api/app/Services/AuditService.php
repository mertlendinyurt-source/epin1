<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Audit Log Service
 */
class AuditService
{
    public const PRODUCT_CREATE = 'product.create';
    public const PRODUCT_UPDATE = 'product.update';
    public const PRODUCT_DELETE = 'product.delete';
    public const STOCK_ADD = 'stock.add';
    public const STOCK_ASSIGN = 'stock.assign';
    public const ORDER_STATUS_CHANGE = 'order.status_change';
    public const SITE_SETTINGS_UPDATE = 'settings.site_update';
    public const OAUTH_SETTINGS_UPDATE = 'settings.oauth_update';
    public const PAYMENT_SETTINGS_UPDATE = 'settings.payment_update';
    public const EMAIL_SETTINGS_UPDATE = 'settings.email_update';
    public const USER_CREATE = 'user.create';
    public const USER_LOGIN = 'user.login';
    public const USER_LOGIN_FAILED = 'user.login_failed';
    public const ADMIN_LOGIN = 'admin.login';
    public const ADMIN_LOGIN_FAILED = 'admin.login_failed';
    public const TICKET_CREATE = 'ticket.create';
    public const TICKET_REPLY = 'ticket.reply';
    public const TICKET_CLOSE = 'ticket.close';
    public const ORDER_RISK_FLAG = 'order.risk_flag';
    public const ORDER_MANUAL_APPROVE = 'order.manual_approve';
    public const ORDER_MANUAL_REFUND = 'order.manual_refund';

    /**
     * Log an audit action
     */
    public function log(
        string $action,
        ?string $actorId,
        ?string $entityType,
        ?string $entityId,
        ?string $ip = null,
        ?string $userAgent = null,
        array $meta = []
    ): ?string {
        try {
            $id = Uuid::uuid4()->toString();

            DB::table('audit_logs')->insert([
                'id' => $id,
                'action' => $action,
                'actor_id' => $actorId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'meta' => json_encode($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        } catch (\Exception $e) {
            // Log failure shouldn't break the application
            return null;
        }
    }

    /**
     * Log from request
     */
    public function logFromRequest(
        string $action,
        ?string $actorId,
        ?string $entityType,
        ?string $entityId,
        array $meta = []
    ): ?string {
        $ip = request()->ip();
        $userAgent = request()->userAgent();

        return $this->log($action, $actorId, $entityType, $entityId, $ip, $userAgent, $meta);
    }
}