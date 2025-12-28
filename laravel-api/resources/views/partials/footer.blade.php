<!-- Footer -->
<footer class="bg-[#0d0e12] border-t border-white/5 mt-16">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="md:col-span-1">
                <div class="flex items-center space-x-2 mb-4">
                    @if(isset($siteSettings['logo']) && $siteSettings['logo'])
                    <img src="{{ $siteSettings['logo'] }}" alt="{{ $siteSettings['siteName'] ?? 'PINLY' }}" class="h-8">
                    @else
                    <span class="text-xl font-bold gradient-text">{{ $siteSettings['siteName'] ?? 'PINLY' }}</span>
                    @endif
                </div>
                <p class="text-sm text-zinc-500">Güvenilir dijital kod satış platformu</p>
                @if(isset($siteSettings['contactEmail']) && $siteSettings['contactEmail'])
                <p class="text-sm text-zinc-500 mt-2">
                    <i data-lucide="mail" class="w-4 h-4 inline mr-1"></i>
                    {{ $siteSettings['contactEmail'] }}
                </p>
                @endif
            </div>
            
            <!-- Hızlı Linkler -->
            <div>
                <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">Hızlı Linkler</h3>
                <ul class="space-y-2">
                    <li><a href="/" class="text-sm text-zinc-500 hover:text-white transition">Ana Sayfa</a></li>
                    <li>
                        <button @click="isAuthenticated ? null : openAuthModal('login')" class="text-sm text-zinc-500 hover:text-white transition">
                            <span x-show="!isAuthenticated">Giriş Yap</span>
                            <a x-show="isAuthenticated" href="/account" class="text-sm text-zinc-500 hover:text-white">Hesabım</a>
                        </button>
                    </li>
                    <li>
                        <button @click="isAuthenticated ? null : openAuthModal('register')" x-show="!isAuthenticated" class="text-sm text-zinc-500 hover:text-white transition">Kayıt Ol</button>
                    </li>
                </ul>
            </div>
            
            <!-- Kategoriler -->
            <div>
                <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">Kategoriler</h3>
                <ul class="space-y-2">
                    <li><a href="/" class="text-sm text-zinc-500 hover:text-white transition">PUBG Mobile</a></li>
                </ul>
            </div>
            
            <!-- Kurumsal -->
            <div>
                <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">Kurumsal</h3>
                <ul class="space-y-2">
                    @foreach($legalPages ?? [] as $page)
                    <li><a href="/legal/{{ $page['slug'] }}" class="text-sm text-zinc-500 hover:text-white transition">{{ $page['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="border-t border-white/5 mt-8 pt-8 text-center">
            <p class="text-sm text-zinc-600">© {{ date('Y') }} {{ $siteSettings['siteName'] ?? 'PINLY' }}. Tüm hakları saklıdır.</p>
        </div>
    </div>
</footer>