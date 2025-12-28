<!-- Header -->
<header class="sticky top-0 z-40 bg-[#0a0b0d]/95 backdrop-blur-md border-b border-white/5">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="/" class="flex items-center space-x-2">
                @if(isset($siteSettings['logo']) && $siteSettings['logo'])
                <img src="{{ $siteSettings['logo'] }}" alt="{{ $siteSettings['siteName'] ?? 'PINLY' }}" class="h-8">
                @else
                <span class="text-xl font-bold gradient-text">{{ $siteSettings['siteName'] ?? 'PINLY' }}</span>
                @endif
            </a>
            
            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center space-x-6">
                <a href="/" class="text-sm text-zinc-400 hover:text-white transition">Ana Sayfa</a>
                <a href="/#products" class="text-sm text-zinc-400 hover:text-white transition">Ürünler</a>
            </nav>
            
            <!-- Auth Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Logged out -->
                <template x-if="!isAuthenticated">
                    <div class="flex items-center space-x-3">
                        <button @click="openAuthModal('login')" class="text-sm text-zinc-400 hover:text-white transition hidden md:block">Giriş Yap</button>
                        <button @click="openAuthModal('register')" class="btn-primary px-4 py-2 text-sm font-medium rounded-lg">Kayıt Ol</button>
                    </div>
                </template>
                
                <!-- Logged in -->
                <template x-if="isAuthenticated">
                    <div class="flex items-center space-x-3" x-data="{ menuOpen: false }">
                        <a href="/account" class="text-sm text-zinc-400 hover:text-white transition hidden md:block">Hesabım</a>
                        <div class="relative">
                            <button @click="menuOpen = !menuOpen" class="flex items-center space-x-2 bg-[#12151a] px-3 py-2 rounded-lg border border-white/10 hover:border-white/20 transition">
                                <i data-lucide="user" class="w-4 h-4 text-zinc-400"></i>
                                <span class="text-sm text-white" x-text="user?.firstName || 'Hesap'"></span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400"></i>
                            </button>
                            
                            <!-- Dropdown -->
                            <div x-show="menuOpen" @click.away="menuOpen = false" x-transition class="absolute right-0 mt-2 w-48 bg-[#12151a] rounded-lg border border-white/10 shadow-xl overflow-hidden">
                                <a href="/account" class="block px-4 py-3 text-sm text-zinc-300 hover:bg-white/5 transition">
                                    <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>Profilim
                                </a>
                                <a href="/account/orders" class="block px-4 py-3 text-sm text-zinc-300 hover:bg-white/5 transition">
                                    <i data-lucide="shopping-bag" class="w-4 h-4 inline mr-2"></i>Siparişlerim
                                </a>
                                <a href="/account/support" class="block px-4 py-3 text-sm text-zinc-300 hover:bg-white/5 transition">
                                    <i data-lucide="message-circle" class="w-4 h-4 inline mr-2"></i>Destek
                                </a>
                                <hr class="border-white/5">
                                <button @click="logout(); menuOpen = false" class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-white/5 transition">
                                    <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i>Çıkış Yap
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                
                <!-- Mobile Menu -->
                <button class="md:hidden p-2" x-data="{ mobileMenuOpen: false }" @click="mobileMenuOpen = !mobileMenuOpen">
                    <i data-lucide="menu" class="w-6 h-6 text-zinc-400"></i>
                </button>
            </div>
        </div>
    </div>
</header>