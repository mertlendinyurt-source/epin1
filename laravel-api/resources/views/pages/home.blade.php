@extends('layouts.main')

@section('content')
<div x-data="homePage()" x-init="init()">
    <!-- Header -->
    @include('partials.header')
    
    <!-- Hero Section with Daily Banner -->
    <section class="relative overflow-hidden">
        <!-- Background -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/20 via-purple-900/10 to-transparent"></div>
        @if(isset($siteSettings['heroImage']) && $siteSettings['heroImage'])
        <div class="absolute inset-0 opacity-10">
            <img src="{{ $siteSettings['heroImage'] }}" class="w-full h-full object-cover" alt="">
        </div>
        @endif
        
        <div class="relative max-w-7xl mx-auto px-4 py-8 md:py-12">
            <!-- Daily Banner -->
            @if($siteSettings['dailyBannerEnabled'] ?? true)
            <div class="mb-8">
                <div class="bg-gradient-to-r from-orange-500/10 via-red-500/10 to-orange-500/10 border border-orange-500/20 rounded-2xl p-4 md:p-6">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-orange-500/20 to-red-500/20 border border-orange-500/30">
                                <i data-lucide="flame" class="w-6 h-6 md:w-8 md:h-8 text-orange-500"></i>
                            </div>
                            <div>
                                <h2 class="text-lg md:text-xl font-bold text-white">{{ $siteSettings['dailyBannerTitle'] ?? 'Bugüne Özel Fiyatlar' }}</h2>
                                <p class="text-sm text-orange-200/70" x-text="todayDate"></p>
                            </div>
                        </div>
                        
                        @if($siteSettings['dailyCountdownEnabled'] ?? true)
                        <!-- Countdown -->
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-zinc-400">{{ $siteSettings['dailyCountdownLabel'] ?? 'Kampanya bitimine' }}:</span>
                            <div class="flex items-center space-x-1">
                                <div class="bg-black/40 rounded-lg px-3 py-2">
                                    <span class="text-lg font-bold text-orange-400" x-text="countdown.hours.toString().padStart(2, '0')">00</span>
                                </div>
                                <span class="text-orange-400 font-bold">:</span>
                                <div class="bg-black/40 rounded-lg px-3 py-2">
                                    <span class="text-lg font-bold text-orange-400" x-text="countdown.minutes.toString().padStart(2, '0')">00</span>
                                </div>
                                <span class="text-orange-400 font-bold">:</span>
                                <div class="bg-black/40 rounded-lg px-3 py-2">
                                    <span class="text-lg font-bold text-orange-400" x-text="countdown.seconds.toString().padStart(2, '0')">00</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Category Header -->
            <div class="flex items-center space-x-4 mb-6">
                @if(isset($siteSettings['categoryIcon']) && $siteSettings['categoryIcon'])
                <img src="{{ $siteSettings['categoryIcon'] }}" alt="PUBG Mobile" class="w-16 h-16 rounded-xl">
                @else
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                    <i data-lucide="gamepad-2" class="w-8 h-8 text-white"></i>
                </div>
                @endif
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">PUBG Mobile UC</h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <div class="flex items-center">
                            @for($i = 0; $i < 5; $i++)
                            <i data-lucide="star" class="w-4 h-4 text-yellow-400 fill-yellow-400"></i>
                            @endfor
                        </div>
                        <span class="text-sm text-zinc-400" x-text="'(' + reviewStats.reviewCount + ' değerlendirme)'"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Products Section -->
    <section class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-xl font-bold text-white mb-6">UC Paketleri</h2>
        
        <!-- Loading -->
        <div x-show="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @for($i = 0; $i < 5; $i++)
            <div class="bg-[#12151a] rounded-xl p-4 animate-pulse">
                <div class="w-full h-32 bg-zinc-800 rounded-lg mb-4"></div>
                <div class="h-4 bg-zinc-800 rounded mb-2"></div>
                <div class="h-6 bg-zinc-800 rounded"></div>
            </div>
            @endfor
        </div>
        
        <!-- Products Grid -->
        <div x-show="!loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <template x-for="product in products" :key="product.id">
                <div class="product-card bg-[#12151a] rounded-xl border border-white/5 overflow-hidden cursor-pointer hover:border-blue-500/50" @click="selectProduct(product)">
                    <!-- Image -->
                    <div class="relative aspect-[4/3] bg-gradient-to-br from-blue-900/20 to-purple-900/20">
                        <img x-show="product.imageUrl" :src="product.imageUrl" :alt="product.title" class="w-full h-full object-cover">
                        <div x-show="!product.imageUrl" class="absolute inset-0 flex items-center justify-center">
                            <i data-lucide="coins" class="w-12 h-12 text-yellow-500"></i>
                        </div>
                        <!-- Discount Badge -->
                        <div x-show="product.discountPercent > 0" class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                            <span x-text="'-' + Math.round(product.discountPercent) + '%'"></span>
                        </div>
                    </div>
                    <!-- Content -->
                    <div class="p-4">
                        <h3 class="text-white font-semibold" x-text="product.title"></h3>
                        <p class="text-sm text-zinc-500" x-text="product.ucAmount + ' UC'"></p>
                        <div class="mt-2 flex items-baseline space-x-2">
                            <span class="text-lg font-bold text-blue-400" x-text="product.discountPrice.toFixed(2) + ' ₺'"></span>
                            <span x-show="product.price > product.discountPrice" class="text-sm text-zinc-500 line-through" x-text="product.price.toFixed(2) + ' ₺'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </section>
    
    <!-- Info Tabs -->
    <section class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-[#12151a] rounded-xl border border-white/5 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-white/5">
                <button @click="activeTab = 'description'" :class="['flex-1 py-4 text-sm font-medium transition', activeTab === 'description' ? 'text-white border-b-2 border-blue-500' : 'text-zinc-400 hover:text-white']">
                    <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i>Açıklama
                </button>
                <button @click="activeTab = 'reviews'" :class="['flex-1 py-4 text-sm font-medium transition', activeTab === 'reviews' ? 'text-white border-b-2 border-blue-500' : 'text-zinc-400 hover:text-white']">
                    <i data-lucide="star" class="w-4 h-4 inline mr-2"></i>Değerlendirmeler
                </button>
            </div>
            
            <!-- Description Tab -->
            <div x-show="activeTab === 'description'" class="p-6">
                <div class="prose prose-invert max-w-none" x-html="gameContent?.description || 'Yükleniyor...'"></div>
            </div>
            
            <!-- Reviews Tab -->
            <div x-show="activeTab === 'reviews'" class="p-6">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="text-4xl font-bold text-white" x-text="reviewStats.avgRating.toFixed(1)"></div>
                    <div>
                        <div class="flex items-center">
                            @for($i = 0; $i < 5; $i++)
                            <i data-lucide="star" class="w-5 h-5 text-yellow-400 fill-yellow-400"></i>
                            @endfor
                        </div>
                        <p class="text-sm text-zinc-400" x-text="reviewStats.reviewCount + ' değerlendirme'"></p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <template x-for="review in reviews" :key="review.id">
                        <div class="bg-[#1a1d24] rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-white" x-text="review.userName"></span>
                                <div class="flex items-center">
                                    <template x-for="i in 5" :key="i">
                                        <i data-lucide="star" :class="['w-4 h-4', i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-zinc-600']"></i>
                                    </template>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-400" x-text="review.comment"></p>
                        </div>
                    </template>
                </div>
                
                <button x-show="reviewsHasMore" @click="loadMoreReviews()" class="mt-4 w-full py-3 bg-[#1a1d24] text-zinc-400 rounded-lg hover:text-white transition">
                    Daha Fazla Yükle
                </button>
            </div>
        </div>
    </section>
    
    <!-- Checkout Modal -->
    @include('partials.checkout-modal')
</div>
@endsection

@push('scripts')
<script>
function homePage() {
    return {
        products: [],
        loading: true,
        selectedProduct: null,
        checkoutOpen: false,
        activeTab: 'description',
        gameContent: null,
        reviews: [],
        reviewStats: { avgRating: 5.0, reviewCount: 0 },
        reviewsPage: 1,
        reviewsHasMore: false,
        todayDate: '',
        countdown: { hours: 0, minutes: 0, seconds: 0 },
        
        init() {
            this.fetchProducts();
            this.fetchGameContent();
            this.fetchReviews();
            this.setTodayDate();
            this.startCountdown();
        },
        
        setTodayDate() {
            this.todayDate = new Date().toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        
        calculateTimeToMidnight() {
            const now = new Date();
            const midnight = new Date(now);
            midnight.setHours(23, 59, 59, 999);
            const diff = midnight.getTime() - now.getTime();
            if (diff <= 0) return { hours: 0, minutes: 0, seconds: 0 };
            return {
                hours: Math.floor(diff / (1000 * 60 * 60)),
                minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
                seconds: Math.floor((diff % (1000 * 60)) / 1000)
            };
        },
        
        startCountdown() {
            this.countdown = this.calculateTimeToMidnight();
            setInterval(() => {
                this.countdown = this.calculateTimeToMidnight();
            }, 1000);
        },
        
        async fetchProducts() {
            try {
                const res = await fetch('/api/products');
                const data = await res.json();
                if (data.success) this.products = data.data;
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        async fetchGameContent() {
            try {
                const res = await fetch('/api/content/pubg');
                const data = await res.json();
                if (data.success) this.gameContent = data.data;
            } catch (e) { console.error(e); }
        },
        
        async fetchReviews(page = 1) {
            try {
                const res = await fetch(`/api/reviews?game=pubg&page=${page}&limit=5`);
                const data = await res.json();
                if (data.success) {
                    if (page === 1) {
                        this.reviews = data.data.reviews;
                    } else {
                        this.reviews = [...this.reviews, ...data.data.reviews];
                    }
                    this.reviewStats = data.data.stats;
                    this.reviewsHasMore = data.data.pagination.hasMore;
                    this.reviewsPage = page;
                }
            } catch (e) { console.error(e); }
        },
        
        loadMoreReviews() {
            this.fetchReviews(this.reviewsPage + 1);
        },
        
        selectProduct(product) {
            this.selectedProduct = product;
            this.checkoutOpen = true;
        }
    }
}
</script>
@endpush