<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Email Service - SMTP Email Sending
 */
class EmailService
{
    private CryptoService $crypto;
    private ?array $settings = null;

    public function __construct(CryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * Get email settings from database
     */
    public function getSettings(): ?array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $settings = DB::table('email_settings')
            ->where('id', 'main')
            ->first();

        if (!$settings) {
            return null;
        }

        $this->settings = [
            'enableEmail' => (bool) $settings->enable_email,
            'fromName' => $settings->from_name,
            'fromEmail' => $settings->from_email,
            'smtpHost' => $settings->smtp_host,
            'smtpPort' => $settings->smtp_port ?? '587',
            'smtpSecure' => (bool) $settings->smtp_secure,
            'smtpUser' => $settings->smtp_user,
            'smtpPass' => $settings->smtp_pass ? $this->crypto->decrypt($settings->smtp_pass) : null,
        ];

        return $this->settings;
    }

    /**
     * Check if email is enabled and configured
     */
    public function isEnabled(): bool
    {
        $settings = $this->getSettings();
        return $settings && $settings['enableEmail'] && $settings['smtpHost'];
    }

    /**
     * Check if email already sent (duplicate prevention)
     */
    public function checkEmailSent(string $type, string $userId, ?string $orderId = null, ?string $ticketId = null): bool
    {
        $query = DB::table('email_logs')
            ->where('type', $type)
            ->where('user_id', $userId);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        if ($ticketId) {
            $query->where('ticket_id', $ticketId);
        }

        return $query->exists();
    }

    /**
     * Log email send attempt
     */
    public function logEmail(
        string $type,
        string $userId,
        string $to,
        string $status,
        ?string $orderId = null,
        ?string $ticketId = null,
        ?string $error = null
    ): void {
        DB::table('email_logs')->insert([
            'id' => Uuid::uuid4()->toString(),
            'type' => $type,
            'user_id' => $userId,
            'order_id' => $orderId,
            'ticket_id' => $ticketId,
            'to' => $to,
            'status' => $status,
            'error' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Send email using SMTP
     */
    public function send(
        string $to,
        string $subject,
        string $html,
        string $type,
        string $userId,
        ?string $orderId = null,
        ?string $ticketId = null,
        bool $skipDuplicateCheck = false
    ): array {
        $settings = $this->getSettings();

        if (!$settings || !$settings['enableEmail']) {
            return ['success' => false, 'reason' => 'disabled'];
        }

        // Duplicate check
        if (!$skipDuplicateCheck && $this->checkEmailSent($type, $userId, $orderId, $ticketId)) {
            return ['success' => false, 'reason' => 'duplicate'];
        }

        try {
            // PHPMailer veya Symfony Mailer kullan
            $transport = (new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $settings['smtpHost'],
                (int) $settings['smtpPort'],
                $settings['smtpSecure']
            ));
            
            $transport->setUsername($settings['smtpUser']);
            $transport->setPassword($settings['smtpPass']);

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address($settings['fromEmail'], $settings['fromName']))
                ->to($to)
                ->subject($subject)
                ->html($html);

            $mailer->send($email);

            $this->logEmail($type, $userId, $to, 'sent', $orderId, $ticketId);

            return ['success' => true];

        } catch (\Exception $e) {
            $this->logEmail($type, $userId, $to, 'failed', $orderId, $ticketId, $e->getMessage());
            return ['success' => false, 'reason' => 'send_failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate premium HTML email template
     */
    public function generateTemplate(array $content): string
    {
        $baseUrl = config('app.url');
        $siteName = DB::table('site_settings')
            ->where('active', true)
            ->value('site_name') ?? 'PINLY';

        $logoUrl = DB::table('site_settings')
            ->where('active', true)
            ->value('logo') ?? '';

        $codesHtml = '';
        if (!empty($content['codes'])) {
            $codesHtml = '<div style="margin-top: 32px; padding: 24px; background-color: #1e2229; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                <h3 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #fbbf24; text-transform: uppercase; letter-spacing: 1px;">
                    🎁 Teslim Edilen Kodlar
                </h3>';
            foreach ($content['codes'] as $code) {
                $codesHtml .= '<div style="margin-bottom: 12px; padding: 16px; background-color: #12151a; border-radius: 8px; border: 1px dashed rgba(59, 130, 246, 0.5);">
                    <code style="font-family: monospace; font-size: 16px; color: #60a5fa; word-break: break-all; letter-spacing: 1px;">' . htmlspecialchars($code) . '</code>
                </div>';
            }
            $codesHtml .= '<p style="margin: 16px 0 0 0; font-size: 12px; color: #f87171;">⚠️ Bu kodları kimseyle paylaşmayın. Güvenliğiniz için saklayın.</p></div>';
        }

        $ctaHtml = '';
        if (!empty($content['cta'])) {
            $ctaHtml = '<div style="margin-top: 32px; text-align: center;">
                <a href="' . htmlspecialchars($content['cta']['url']) . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);">
                    ' . htmlspecialchars($content['cta']['text']) . '
                </a>
            </div>';
        }

        $infoHtml = '';
        if (!empty($content['info'])) {
            $infoHtml = '<div style="margin-top: 24px; padding: 20px; background-color: rgba(59, 130, 246, 0.1); border-radius: 8px; border-left: 4px solid #3b82f6;">
                <p style="margin: 0; font-size: 14px; color: #93c5fd;">' . htmlspecialchars($content['info']) . '</p>
            </div>';
        }

        $warningHtml = '';
        if (!empty($content['warning'])) {
            $warningHtml = '<div style="margin-top: 24px; padding: 20px; background-color: rgba(239, 68, 68, 0.1); border-radius: 8px; border-left: 4px solid #ef4444;">
                <p style="margin: 0; font-size: 14px; color: #fca5a5;">⚠️ ' . htmlspecialchars($content['warning']) . '</p>
            </div>';
        }

        return '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($content['subject']) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif; background-color: #0a0b0d; color: #ffffff;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #12151a; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.4);">
                    <tr>
                        <td style="padding: 32px 40px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); text-align: center;">
                            ' . ($logoUrl ? '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($siteName) . '" style="height: 48px; margin-bottom: 12px;">' : '') . '
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">' . htmlspecialchars($siteName) . '</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 24px 0; font-size: 22px; font-weight: 600; color: #ffffff; line-height: 1.3;">
                                ' . htmlspecialchars($content['title']) . '
                            </h2>
                            <div style="font-size: 15px; line-height: 1.7; color: #a1a1aa;">
                                ' . $content['body'] . '
                            </div>
                            ' . $ctaHtml . '
                            ' . $codesHtml . '
                            ' . $infoHtml . '
                            ' . $warningHtml . '
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #0a0b0d; border-top: 1px solid rgba(255,255,255,0.05);">
                            <p style="margin: 0 0 8px 0; font-size: 13px; color: #71717a; text-align: center;">
                                ' . htmlspecialchars($siteName) . ' - Güvenilir UC Satış Platformu
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #52525b; text-align: center;">
                                <a href="' . $baseUrl . '/legal/terms" style="color: #52525b; text-decoration: none;">Kullanım Şartları</a>
                                &nbsp;•&nbsp;
                                <a href="' . $baseUrl . '/legal/privacy" style="color: #52525b; text-decoration: none;">Gizlilik Politikası</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Send welcome email
     */
    public function sendWelcome(array $user): array
    {
        $content = [
            'subject' => 'Hoş geldin, ' . $user['first_name'] . '! 🎮',
            'title' => 'Merhaba ' . $user['first_name'] . '!',
            'body' => '<p>PINLY ailesine hoş geldin!</p><p>Hesabın başarıyla oluşturuldu. Artık en uygun fiyatlarla UC satın alabilir ve anında teslimat alabilirsin.</p>',
            'cta' => [
                'text' => 'Alışverişe Başla',
                'url' => config('app.url'),
            ],
            'info' => 'Sorularınız için destek talebi oluşturabilirsiniz.',
        ];

        $html = $this->generateTemplate($content);
        return $this->send($user['email'], $content['subject'], $html, 'welcome', $user['id']);
    }

    /**
     * Send delivery email with codes
     */
    public function sendDelivered(array $order, array $user, array $product, array $codes): array
    {
        $content = [
            'subject' => 'Teslimat tamamlandı — #' . substr($order['id'], -8),
            'title' => 'Teslimat Tamamlandı! 🎉',
            'body' => '<p>Merhaba ' . $user['first_name'] . ',</p><p>Harika haber! Siparişiniz başarıyla teslim edildi.</p>',
            'codes' => $codes,
            'cta' => [
                'text' => 'Sipariş Detaylarını Gör',
                'url' => config('app.url') . '/account/orders/' . $order['id'],
            ],
            'warning' => 'Bu kodları kimseyle paylaşmayın! Kodlar tek kullanımlıktır.',
        ];

        $html = $this->generateTemplate($content);
        return $this->send($user['email'], $content['subject'], $html, 'delivered', $user['id'], $order['id']);
    }

    /**
     * Send password changed notification
     */
    public function sendPasswordChanged(array $user): array
    {
        $content = [
            'subject' => 'Şifreniz değiştirildi ⚠️',
            'title' => 'Şifre Değişikliği Bildirimi',
            'body' => '<p>Merhaba ' . $user['first_name'] . ',</p><p>Hesabınızın şifresi başarıyla değiştirildi.</p>',
            'warning' => 'Bu işlemi siz yapmadıysanız, hemen destek ekibiyle iletişime geçin!',
            'cta' => [
                'text' => 'Destek Talebi Oluştur',
                'url' => config('app.url') . '/account/support/new',
            ],
        ];

        $html = $this->generateTemplate($content);
        return $this->send($user['email'], $content['subject'], $html, 'password_changed', $user['id'], null, null, true);
    }

    /**
     * Send support reply notification
     */
    public function sendSupportReply(array $ticket, array $user, string $adminMessage): array
    {
        $preview = strlen($adminMessage) > 200 ? substr($adminMessage, 0, 200) . '...' : $adminMessage;

        $content = [
            'subject' => 'Destek talebinize yanıt var — #' . substr($ticket['id'], -8),
            'title' => 'Destek Ekibinden Yanıt 💬',
            'body' => '<p>Merhaba ' . $user['first_name'] . ',</p><p>Destek talebinize yanıt verildi.</p>
                <div style="margin-top: 20px; padding: 20px; background-color: #1e2229; border-radius: 8px;">
                    <p style="margin: 0 0 12px 0; font-size: 12px; color: #71717a; text-transform: uppercase;">Talep: ' . htmlspecialchars($ticket['subject']) . '</p>
                    <div style="padding: 16px; background-color: #12151a; border-radius: 8px; border-left: 3px solid #22c55e;">
                        <p style="margin: 0; font-size: 14px; color: #d4d4d8; line-height: 1.6;">"' . htmlspecialchars($preview) . '"</p>
                    </div>
                </div>',
            'cta' => [
                'text' => 'Yanıtı Görüntüle',
                'url' => config('app.url') . '/account/support/' . $ticket['id'],
            ],
            'info' => 'Artık siz de yanıt verebilirsiniz.',
        ];

        $html = $this->generateTemplate($content);
        return $this->send($user['email'], $content['subject'], $html, 'support_reply', $user['id'], null, $ticket['id'], true);
    }
}