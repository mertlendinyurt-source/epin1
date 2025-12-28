<?php

namespace App\Services;

/**
 * Crypto Service - AES-256-GCM Encryption
 * Birebir Next.js/lib/crypto.js ile uyumlu
 */
class CryptoService
{
    private const ALGORITHM = 'aes-256-gcm';
    private const KEY_LENGTH = 32;
    private const IV_LENGTH = 16;
    private const AUTH_TAG_LENGTH = 16;

    /**
     * Get master encryption key from environment
     */
    private function getMasterKey(): string
    {
        $masterKey = env('MASTER_ENCRYPTION_KEY');
        
        if (!$masterKey) {
            throw new \RuntimeException('MASTER_ENCRYPTION_KEY not found in environment variables');
        }
        
        // Hash ile 32 byte key oluştur (Next.js ile aynı)
        return hash('sha256', $masterKey, true);
    }

    /**
     * Encrypt sensitive data using AES-256-GCM
     * @param string|null $plaintext
     * @return string|null Base64 encoded encrypted data
     */
    public function encrypt(?string $plaintext): ?string
    {
        if (!$plaintext) {
            return null;
        }

        try {
            $key = $this->getMasterKey();
            $iv = random_bytes(self::IV_LENGTH);
            
            $encrypted = openssl_encrypt(
                $plaintext,
                self::ALGORITHM,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                self::AUTH_TAG_LENGTH
            );
            
            if ($encrypted === false) {
                throw new \RuntimeException('Encryption failed');
            }
            
            // IV + encrypted + authTag birleştir
            $combined = $iv . $encrypted . $tag;
            
            return base64_encode($combined);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to encrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt sensitive data using AES-256-GCM
     * @param string|null $encryptedData Base64 encoded encrypted data
     * @return string|null Decrypted plaintext
     */
    public function decrypt(?string $encryptedData): ?string
    {
        if (!$encryptedData) {
            return null;
        }

        try {
            $key = $this->getMasterKey();
            $combined = base64_decode($encryptedData);
            
            if ($combined === false || strlen($combined) < self::IV_LENGTH + self::AUTH_TAG_LENGTH) {
                throw new \RuntimeException('Invalid encrypted data');
            }
            
            // IV, encrypted, authTag ayır
            $iv = substr($combined, 0, self::IV_LENGTH);
            $tag = substr($combined, -self::AUTH_TAG_LENGTH);
            $encrypted = substr($combined, self::IV_LENGTH, -self::AUTH_TAG_LENGTH);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                self::ALGORITHM,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed');
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to decrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Mask sensitive data for display (first 4 and last 4 chars)
     */
    public function maskSensitiveData(?string $data): string
    {
        if (!$data || strlen($data) < 8) {
            return '****';
        }
        
        $first = substr($data, 0, 4);
        $last = substr($data, -4);
        $middle = str_repeat('*', max(4, strlen($data) - 8));
        
        return $first . $middle . $last;
    }

    /**
     * Generate Shopier callback hash
     */
    public function generateShopierHash(string $orderId, $amount, string $secret): string
    {
        $data = $orderId . $amount . $secret;
        return hash('sha256', $data);
    }
}