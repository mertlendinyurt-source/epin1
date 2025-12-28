<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user
        $adminExists = DB::table('admin_users')->where('username', 'admin')->exists();
        if (!$adminExists) {
            DB::table('admin_users')->insert([
                'id' => Uuid::uuid4()->toString(),
                'username' => 'admin',
                'password_hash' => Hash::make('admin123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default products
        $productsCount = DB::table('products')->count();
        if ($productsCount === 0) {
            $products = [
                [
                    'id' => Uuid::uuid4()->toString(),
                    'title' => '60 UC',
                    'uc_amount' => 60,
                    'price' => 25.00,
                    'discount_price' => 19.99,
                    'discount_percent' => 20.04,
                    'active' => true,
                    'sort_order' => 1,
                    'image_url' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=400&h=300&fit=crop',
                    'region_code' => 'TR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'title' => '325 UC',
                    'uc_amount' => 325,
                    'price' => 100.00,
                    'discount_price' => 89.99,
                    'discount_percent' => 10.01,
                    'active' => true,
                    'sort_order' => 2,
                    'image_url' => 'https://images.unsplash.com/photo-1552820728-8b83bb6b773f?w=400&h=300&fit=crop',
                    'region_code' => 'TR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'title' => '660 UC',
                    'uc_amount' => 660,
                    'price' => 200.00,
                    'discount_price' => 179.99,
                    'discount_percent' => 10.01,
                    'active' => true,
                    'sort_order' => 3,
                    'image_url' => 'https://images.unsplash.com/photo-1579373903781-fd5c0c30c4cd?w=400&h=300&fit=crop',
                    'region_code' => 'TR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'title' => '1800 UC',
                    'uc_amount' => 1800,
                    'price' => 500.00,
                    'discount_price' => 449.99,
                    'discount_percent' => 10.00,
                    'active' => true,
                    'sort_order' => 4,
                    'image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400&h=300&fit=crop',
                    'region_code' => 'TR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'title' => '3850 UC',
                    'uc_amount' => 3850,
                    'price' => 1000.00,
                    'discount_price' => 899.99,
                    'discount_percent' => 10.00,
                    'active' => true,
                    'sort_order' => 5,
                    'image_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=400&h=300&fit=crop',
                    'region_code' => 'TR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('products')->insert($products);
        }

        // Create default regions
        $regionsCount = DB::table('regions')->count();
        if ($regionsCount === 0) {
            $regions = [
                ['id' => Uuid::uuid4()->toString(), 'code' => 'TR', 'name' => 'Türkiye', 'enabled' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['id' => Uuid::uuid4()->toString(), 'code' => 'GLOBAL', 'name' => 'Küresel', 'enabled' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['id' => Uuid::uuid4()->toString(), 'code' => 'DE', 'name' => 'Almanya', 'enabled' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['id' => Uuid::uuid4()->toString(), 'code' => 'FR', 'name' => 'Fransa', 'enabled' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
                ['id' => Uuid::uuid4()->toString(), 'code' => 'JP', 'name' => 'Japonya', 'enabled' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ];

            DB::table('regions')->insert($regions);
        }

        // Create default site settings
        $siteSettingsExists = DB::table('site_settings')->where('active', true)->exists();
        if (!$siteSettingsExists) {
            DB::table('site_settings')->insert([
                'id' => Uuid::uuid4()->toString(),
                'site_name' => 'PINLY',
                'meta_title' => 'PINLY – Dijital Kod ve Oyun Satış Platformu',
                'meta_description' => 'PUBG Mobile UC satın al. Güvenilir, hızlı ve uygun fiyatlı UC satış platformu.',
                'daily_banner_enabled' => true,
                'daily_banner_title' => 'Bugüne Özel Fiyatlar',
                'daily_banner_icon' => 'fire',
                'daily_countdown_enabled' => true,
                'daily_countdown_label' => 'Kampanya bitimine',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default game content
        $gameContentExists = DB::table('game_content')->where('game', 'pubg')->exists();
        if (!$gameContentExists) {
            DB::table('game_content')->insert([
                'game' => 'pubg',
                'title' => 'PUBG Mobile',
                'description' => "# PUBG Mobile UC Satın Al\n\nPUBG Mobile, dünyanın en popüler battle royale oyunlarından biridir. Unknown Cash (UC), oyun içi para birimidir ve çeşitli kozmetik eşyalar, silah skinleri ve Royale Pass satın almak için kullanılır.\n\n## UC ile Neler Yapabilirsiniz?\n\n- **Royale Pass**: Her sezon yeni Royale Pass satın alarak özel ödüller kazanın\n- **Silah Skinleri**: Nadir ve efsanevi silah görünümleri\n- **Karakter Kıyafetleri**: Karakterinizi özelleştirin\n- **Araç Skinleri**: Benzersiz araç görünümleri\n- **Emote ve Danslar**: Eğlenceli hareketler\n\n## Neden Bizi Tercih Etmelisiniz?\n\n✓ **Anında Teslimat**: Ödeme onaylandıktan sonra kodunuz anında teslim edilir\n✓ **Güvenli Ödeme**: SSL şifrelemeli güvenli ödeme altyapısı\n✓ **7/24 Destek**: Her zaman yanınızdayız\n✓ **En Uygun Fiyat**: Piyasadaki en rekabetçi fiyatlar",
                'default_rating' => 5.0,
                'default_review_count' => 2008,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}