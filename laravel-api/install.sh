#!/bin/bash

# PINLY UC Store - cPanel Kurulum Scripti
# Bu scripti SSH ile çalıştırın

echo "=== PINLY Laravel API Kurulumu ==="
echo ""

# .env dosyasını kontrol et
if [ ! -f ".env" ]; then
    echo "HATA: .env dosyası bulunamadı!"
    echo "Lütfen .env.example dosyasını .env olarak kopyalayın ve düzenleyin."
    exit 1
fi

echo "1. Composer bağımlılıkları yükleniyor..."
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "2. Uygulama anahtarı oluşturuluyor..."
php artisan key:generate --force

echo ""
echo "3. Veritabanı migrasyonları çalıştırılıyor..."
php artisan migrate --force

echo ""
echo "4. Varsayılan veriler yükleniyor..."
php artisan db:seed --force

echo ""
echo "5. Cache temizleniyor ve yeniden oluşturuluyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "6. Storage klasörü linki oluşturuluyor..."
php artisan storage:link

echo ""
echo "7. Dosya izinleri ayarlanıyor..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo ""
echo "=== KURULUM TAMAMLANDI ==="
echo ""
echo "Varsayılan Admin Girişi:"
echo "  Kullanıcı Adı: admin"
echo "  Şifre: admin123"
echo ""
echo "ÖNEMLİ: Production'da admin şifresini değiştirin!"
echo ""