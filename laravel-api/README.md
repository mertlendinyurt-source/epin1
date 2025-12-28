# PINLY UC Store - Laravel API

## Kurulum (cPanel)

### 1. Dosyaları Yükleyin
- Tüm dosyaları `public_html` veya alt klasöre yükleyin
- `storage` ve `bootstrap/cache` klasörlerine yazma izni verin (chmod 775)

### 2. Veritabanı Oluşturun
cPanel > MySQL Databases:
1. Yeni veritabanı oluşturun: `username_pinly`
2. Yeni kullanıcı oluşturun: `username_pinly`
3. Kullanıcıyı veritabanına ekleyin (Tüm izinler)

### 3. .env Dosyasını Düzenleyin
```env
DB_DATABASE=username_pinly
DB_USERNAME=username_pinly
DB_PASSWORD=your_password
APP_URL=https://pinly.com.tr
```

### 4. Composer Install
Terminal veya SSH ile:
```bash
cd /home/username/public_html
composer install --no-dev --optimize-autoloader
```

### 5. Migration ve Seed
```bash
php artisan migrate
php artisan db:seed
```

### 6. Cache Temizle
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. .htaccess
`public` klasöründeki .htaccess'in aktif olduğundan emin olun.

Eğer root'a kuruyorsanız, ana dizine şu .htaccess'i ekleyin:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## Varsayılan Admin Girişi
- **Kullanıcı Adı:** admin
- **Şifre:** admin123

**ÖNEMLİ: Production'da şifreyi değiştirin!**

---

## API Endpoint'leri

Tüm endpoint'ler `/api` prefix'i ile çalışır:

### Public
- `GET /api/products` - Ürün listesi
- `GET /api/regions` - Bölgeler
- `GET /api/site/settings` - Site ayarları
- `GET /api/player/resolve?id=xxx` - Oyuncu adı sorgulama

### Auth
- `POST /api/auth/register` - Kayıt
- `POST /api/auth/login` - Giriş
- `GET /api/auth/google` - Google OAuth

### User (JWT Required)
- `GET /api/account/orders` - Siparişlerim
- `POST /api/orders` - Sipariş oluştur
- `POST /api/support/tickets` - Destek talebi

### Admin (Admin JWT Required)
- `POST /api/admin/login` - Admin giriş
- `GET /api/admin/dashboard` - Dashboard
- `GET /api/admin/orders` - Siparişler
- `GET /api/admin/products` - Ürünler
- `POST /api/admin/settings/payments` - Shopier ayarları
- `POST /api/admin/settings/oauth/google` - Google OAuth ayarları

---

## Güvenlik

- Tüm hassas veriler AES-256-GCM ile şifrelenmiştir
- JWT token kullanılır (7 gün geçerli)
- Rate limiting aktif
- CSRF ve CORS korumalı

---

## Frontend Entegrasyonu

React frontend'i `public/assets` altına koyun veya ayrı bir subdomain kullanın.

API URL: `https://pinly.com.tr/api`

Auth header: `Authorization: Bearer <token>`