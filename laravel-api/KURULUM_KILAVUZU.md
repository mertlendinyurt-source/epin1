# PINLY UC Store - Laravel API Kurulum Kılavuzu

## 📋 Gereksinimler

- PHP 8.1 veya üstü
- MySQL 5.7+ veya MariaDB 10.3+
- Composer
- cPanel erişimi

---

## 🚀 Adım Adım Kurulum

### Adım 1: Dosyaları Yükleyin

1. **laravel-api.zip** dosyasını bilgisayarınıza indirin
2. cPanel → File Manager → public_html klasörüne gidin
3. ZIP dosyasını yükleyin ve çıkarın
4. Sonuç olarak `public_html/laravel-api/` klasörü oluşmalı

> **Not:** Domain'in direkt API'ye gitmesi için:
> - `public_html` içindeki tüm dosyaları silin (sadece laravel-api klasörünü bırakın)
> - `laravel-api` klasörünün içindekileri `public_html`'e taşıyın

### Adım 2: Veritabanı Oluşturun

cPanel → MySQL Databases:

1. **Create New Database**: `kullaniciadi_pinly`
2. **Create New User**: `kullaniciadi_pinly` + güçlü şifre
3. **Add User to Database**: Kullanıcıyı veritabanına ekleyin
4. **Tüm yetkileri (ALL PRIVILEGES)** verin

### Adım 3: .env Dosyasını Düzenleyin

cPanel → File Manager → `.env` dosyasını düzenleyin:

```env
APP_NAME=PINLY
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pinly.com.tr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=kullaniciadi_pinly
DB_USERNAME=kullaniciadi_pinly
DB_PASSWORD=VERITABANI_SIFRENIZ

JWT_SECRET=en-az-32-karakterlik-guvenli-anahtar-uretın

MASTER_ENCRYPTION_KEY=openssl-rand-base64-32-ile-olusturun

RAPIDAPI_KEY=60cf92cec8mshdb515eedb9ab9afp1ee982jsn7c4921aa82b1
```

### Adım 4: Veritabanı Tablolarını Oluşturun

**Seçenek A - phpMyAdmin ile:**

1. cPanel → phpMyAdmin → veritabanınızı seçin
2. "SQL" sekmesine gidin
3. `database/schema.sql` dosyasının içeriğini yapıştırın
4. "Çalıştır" butonuna tıklayın

**Seçenek B - SSH ile (Terminal):**

```bash
cd ~/public_html
mysql -u kullaniciadi_pinly -p kullaniciadi_pinly < database/schema.sql
```

### Adım 5: Composer Bağımlılıklarını Yükleyin

cPanel → Terminal (veya SSH):

```bash
cd ~/public_html
composer install --no-dev --optimize-autoloader
```

> **Not:** Eğer Composer yüklü değilse, cPanel'in "Setup PHP Version" bölümünden Composer'ı aktifleştirin.

### Adım 6: Dosya İzinlerini Ayarlayın

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Adım 7: Cache'i Temizleyin

```bash
php artisan config:cache
php artisan route:cache
```

---

## 🔐 Varsayılan Admin Girişi

| Alan | Değer |
|------|-------|
| **Kullanıcı Adı** | admin |
| **Şifre** | admin123 |

⚠️ **ÖNEMLİ:** Production'da bu şifreyi hemen değiştirin!

---

## 🌐 Domain Yapılandırması

### pinly.com.tr için:

Domain'in `public` klasörüne yönlenmesi gerekiyor.

**Seçenek 1 - .htaccess (Önerilen)**

Ana dizine bu .htaccess'i ekleyin:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

**Seçenek 2 - Subdomain**

- API için: `api.pinly.com.tr` → `public_html/laravel-api/public`
- Frontend için: `pinly.com.tr` → React build klasörü

---

## 📡 API Endpoint'leri

Base URL: `https://pinly.com.tr/api`

### Public (Kimlik doğrulama gerekmez)

| Endpoint | Açıklama |
|----------|----------|
| `GET /products` | Ürün listesi |
| `GET /regions` | Bölgeler |
| `GET /site/settings` | Site ayarları |
| `GET /player/resolve?id=xxx` | Oyuncu adı sorgulama |
| `GET /reviews` | Değerlendirmeler |
| `GET /legal/{slug}` | Yasal sayfalar |

### Auth (Kimlik doğrulama)

| Endpoint | Açıklama |
|----------|----------|
| `POST /auth/register` | Kayıt ol |
| `POST /auth/login` | Giriş yap |
| `GET /auth/google` | Google ile giriş |

### User (JWT Token gerekli)

| Endpoint | Açıklama |
|----------|----------|
| `GET /account/orders` | Siparişlerim |
| `GET /account/orders/{id}` | Sipariş detayı |
| `POST /orders` | Sipariş oluştur |
| `POST /support/tickets` | Destek talebi |

### Admin (Admin JWT Token gerekli)

| Endpoint | Açıklama |
|----------|----------|
| `POST /admin/login` | Admin giriş |
| `GET /admin/dashboard` | Dashboard |
| `GET /admin/orders` | Tüm siparişler |
| `GET /admin/products` | Ürün yönetimi |
| `POST /admin/settings/payments` | Shopier ayarları |
| `POST /admin/settings/oauth/google` | Google OAuth |

---

## 🔧 Admin Panel Ayarları

### Shopier Entegrasyonu

1. Admin panelden Settings → Payment'a gidin
2. Shopier API Key ve Secret'ı girin
3. Kaydet'e tıklayın

### Google OAuth

1. Google Cloud Console'dan OAuth credentials oluşturun
2. Authorized redirect URI: `https://pinly.com.tr/api/auth/google/callback`
3. Admin panelden Settings → OAuth → Google'a gidin
4. Client ID ve Secret'ı girin
5. "Enabled" yapın ve kaydedin

### E-posta Bildirimleri

1. SMTP sunucu bilgilerinizi hazırlayın
2. Admin panelden Settings → Email'e gidin
3. SMTP ayarlarını girin
4. Test e-postası göndererek doğrulayın

---

## 🔄 React Frontend Entegrasyonu

Frontend'in Laravel API ile çalışması için:

1. **API Base URL**:
   ```javascript
   const API_URL = 'https://pinly.com.tr/api';
   ```

2. **Auth Token**:
   ```javascript
   fetch(url, {
     headers: {
       'Authorization': `Bearer ${token}`,
       'Content-Type': 'application/json'
     }
   });
   ```

3. **CORS**: `.env` dosyasında frontend domain'ini ekleyin:
   ```env
   CORS_ALLOWED_ORIGINS=https://pinly.com.tr,https://www.pinly.com.tr
   ```

---

## ⚠️ Sık Karşılaşılan Sorunlar

### 500 Internal Server Error

1. `.env` dosyasının doğru yapılandırıldığından emin olun
2. `storage` ve `bootstrap/cache` klasörlerine yazma izni verin
3. `php artisan config:cache` çalıştırın

### Veritabanı Bağlantı Hatası

1. Veritabanı adı, kullanıcı ve şifreyi kontrol edin
2. Kullanıcının veritabanına erişim yetkisi olduğundan emin olun

### 404 Not Found

1. `.htaccess` dosyasının mevcut olduğundan emin olun
2. `mod_rewrite` modülünün aktif olduğunu kontrol edin

### JWT Token Hatası

1. `JWT_SECRET` değerinin .env'de doğru ayarlandığından emin olun
2. En az 32 karakter uzunluğunda bir secret kullanın

---

## 📞 Destek

Sorularınız için:
- Dokümantasyon: `README.md`
- E-posta: destek@pinly.com.tr

---

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.
