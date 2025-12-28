<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;

/**
 * Shopier Payment Service
 * Production entegrasyonu - birebir Next.js ile uyumlu
 */
class ShopierService
{
    private CryptoService $crypto;
    private const SHOPIER_API_URL = 'https://www.shopier.com/api/v1/payment';
    private const SHOPIER_PAYMENT_URL = 'https://www.shopier.com/ShowProduct/api_pay4.php';

    public function __construct(CryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * Get decrypted Shopier settings from database
     */
    public function getSettings(): ?array
    {
        $settings = DB::table('shopier_settings')
            ->where('is_active', true)
            ->first();

        if (!$settings) {
            return null;
        }

        return [
            'apiKey' => $settings->api_key ? $this->crypto->decrypt($settings->api_key) : null,
            'apiSecret' => $settings->api_secret ? $this->crypto->decrypt($settings->api_secret) : null,
            'mode' => $settings->mode ?? 'production',
        ];
    }

    /**
     * Check if Shopier is configured
     */
    public function isConfigured(): bool
    {
        $settings = $this->getSettings();
        return $settings && !empty($settings['apiKey']) && !empty($settings['apiSecret']);
    }

    /**
     * Generate payment URL for order
     */
    public function createPayment(array $order, array $product, array $user): array
    {
        $settings = $this->getSettings();
        
        if (!$settings || empty($settings['apiKey']) || empty($settings['apiSecret'])) {
            throw new \RuntimeException('Shopier ayarları yapılandırılmamış');
        }

        $baseUrl = config('app.url');
        $callbackUrl = $baseUrl . '/api/payment/shopier/callback';
        $returnUrl = $baseUrl . '/order/' . $order['id'] . '/status';

        // Shopier API formatında veri hazırla
        $paymentData = [
            'API_key' => $settings['apiKey'],
            'website_index' => 1,
            'platform_order_id' => $order['id'],
            'product_name' => $product['title'] . ' - ' . $product['uc_amount'] . ' UC',
            'product_type' => 0, // Dijital ürün
            'buyer_name' => $user['first_name'],
            'buyer_surname' => $user['last_name'],
            'buyer_email' => $user['email'],
            'buyer_phone' => $user['phone'] ?? '5000000000',
            'buyer_account_age' => 0,
            'buyer_id_nr' => '',
            'billing_address' => 'Dijital Ürün',
            'billing_city' => 'Istanbul',
            'billing_country' => 'TR',
            'billing_postcode' => '34000',
            'shipping_address' => 'Dijital Ürün',
            'shipping_city' => 'Istanbul',
            'shipping_country' => 'TR',
            'shipping_postcode' => '34000',
            'total_order_value' => number_format((float)$product['discount_price'], 2, '.', ''),
            'currency' => 0, // TRY
            'current_language' => 1, // Türkçe
            'modul_version' => '1.0.0',
            'random_nr' => substr(md5(uniqid()), 0, 16),
        ];

        // Signature oluştur
        $signatureData = base64_encode(json_encode($paymentData));
        $signature = base64_encode(hash_hmac('sha256', $signatureData, $settings['apiSecret'], true));

        // Payment request log
        DB::table('payment_requests')->insert([
            'id' => Uuid::uuid4()->toString(),
            'order_id' => $order['id'],
            'api_key_masked' => $this->crypto->maskSensitiveData($settings['apiKey']),
            'payment_url' => self::SHOPIER_PAYMENT_URL,
            'request_data' => json_encode(['orderId' => $order['id'], 'amount' => $product['discount_price']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'paymentUrl' => self::SHOPIER_PAYMENT_URL,
            'paymentData' => $signatureData,
            'signature' => $signature,
        ];
    }

    /**
     * Validate callback hash
     */
    public function validateCallbackHash(string $orderId, $amount, string $receivedHash): bool
    {
        $settings = $this->getSettings();
        
        if (!$settings || empty($settings['apiSecret'])) {
            return false;
        }

        $expectedHash = $this->crypto->generateShopierHash($orderId, $amount, $settings['apiSecret']);
        
        return hash_equals($expectedHash, $receivedHash);
    }

    /**
     * Process payment callback
     */
    public function processCallback(array $data): array
    {
        $orderId = $data['platform_order_id'] ?? $data['orderId'] ?? null;
        $status = $data['status'] ?? $data['payment_status'] ?? null;
        $transactionId = $data['random_nr'] ?? $data['transactionId'] ?? Uuid::uuid4()->toString();
        $amount = $data['total_order_value'] ?? $data['amount'] ?? 0;
        $hash = $data['signature'] ?? $data['hash'] ?? '';

        if (!$orderId) {
            throw new \InvalidArgumentException('Order ID eksik');
        }

        // Hash validasyonu
        $hashValid = $this->validateCallbackHash($orderId, $amount, $hash);

        return [
            'orderId' => $orderId,
            'status' => $status === 'success' || $status === '1' ? 'success' : 'failed',
            'transactionId' => $transactionId,
            'amount' => (float) $amount,
            'hashValid' => $hashValid,
            'rawPayload' => $data,
        ];
    }
}