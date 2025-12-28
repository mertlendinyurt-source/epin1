<!-- Checkout Modal -->
<div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data="checkoutModal()">
    <!-- Backdrop -->
    <div class="absolute inset-0 modal-backdrop" @click="checkoutOpen = false"></div>
    
    <!-- Modal -->
    <div class="relative bg-[#12151a] rounded-2xl shadow-2xl w-full max-w-lg border border-white/10 overflow-hidden max-h-[90vh] overflow-y-auto" x-transition>
        <!-- Header -->
        <div class="sticky top-0 bg-[#12151a] border-b border-white/10 px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-lg font-semibold text-white">Sipariş Ver</h2>
            <button @click="checkoutOpen = false" class="text-zinc-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="p-6">
            <!-- Product Summary -->
            <div class="bg-[#1a1d24] rounded-xl p-4 mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-blue-900/40 to-purple-900/40 flex items-center justify-center">
                        <img x-show="selectedProduct?.imageUrl" :src="selectedProduct?.imageUrl" class="w-full h-full object-cover rounded-lg">
                        <i x-show="!selectedProduct?.imageUrl" data-lucide="coins" class="w-8 h-8 text-yellow-500"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white" x-text="selectedProduct?.title"></h3>
                        <p class="text-sm text-zinc-400" x-text="selectedProduct?.ucAmount + ' UC'"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-blue-400" x-text="selectedProduct?.discountPrice?.toFixed(2) + ' ₺'"></p>
                    </div>
                </div>
            </div>
            
            <!-- Step 1: Player ID -->
            <div x-show="step === 1">
                <label class="block text-sm font-medium text-zinc-300 mb-2">PUBG Mobile Oyuncu ID</label>
                <div class="relative">
                    <input type="text" x-model="playerId" @input="validatePlayerId()" 
                           class="w-full px-4 py-3 bg-[#1a1d24] border rounded-lg text-white placeholder-zinc-500 focus:outline-none transition"
                           :class="playerValid === true ? 'border-green-500' : playerValid === false ? 'border-red-500' : 'border-white/10 focus:border-blue-500'"
                           placeholder="Örneğin: 5123456789">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                        <i x-show="playerLoading" data-lucide="loader-2" class="w-5 h-5 text-blue-400 animate-spin"></i>
                        <i x-show="playerValid === true && !playerLoading" data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                        <i x-show="playerValid === false && !playerLoading" data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>
                    </div>
                </div>
                
                <!-- Player Name Display -->
                <div x-show="playerName && playerValid" class="mt-3 p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <p class="text-sm text-green-400">
                        <i data-lucide="user" class="w-4 h-4 inline mr-1"></i>
                        Oyuncu: <span class="font-semibold" x-text="playerName"></span>
                    </p>
                </div>
                
                <p x-show="playerError" class="mt-2 text-sm text-red-400" x-text="playerError"></p>
                
                <button @click="proceedToPayment()" :disabled="!playerValid || playerLoading" 
                        class="w-full mt-6 py-3 btn-primary text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    Ödemeye Geç
                </button>
            </div>
            
            <!-- Step 2: Auth Required (if not logged in) -->
            <div x-show="step === 2">
                <div class="text-center py-4">
                    <i data-lucide="user-circle" class="w-16 h-16 mx-auto text-zinc-600 mb-4"></i>
                    <h3 class="text-lg font-semibold text-white mb-2">Giriş Gerekli</h3>
                    <p class="text-sm text-zinc-400 mb-6">Sipariş vermek için giriş yapmalı veya kayıt olmalısınız.</p>
                    
                    <div class="space-y-3">
                        <button @click="checkoutOpen = false; $root.__x.$data.openAuthModal('login')" class="w-full py-3 btn-primary text-white font-medium rounded-lg">
                            Giriş Yap
                        </button>
                        <button @click="checkoutOpen = false; $root.__x.$data.openAuthModal('register')" class="w-full py-3 bg-[#1a1d24] text-white font-medium rounded-lg border border-white/10 hover:border-white/20 transition">
                            Kayıt Ol
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Processing -->
            <div x-show="step === 3" class="text-center py-8">
                <svg class="animate-spin h-12 w-12 mx-auto text-blue-500 mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-zinc-400">Sipariş oluşturuluyor...</p>
            </div>
        </div>
    </div>
</div>

<script>
function checkoutModal() {
    return {
        step: 1,
        playerId: '',
        playerName: '',
        playerValid: null,
        playerLoading: false,
        playerError: '',
        validateTimeout: null,
        
        validatePlayerId() {
            clearTimeout(this.validateTimeout);
            this.playerValid = null;
            this.playerName = '';
            this.playerError = '';
            
            if (this.playerId.length < 6) return;
            
            this.validateTimeout = setTimeout(() => this.checkPlayer(), 500);
        },
        
        async checkPlayer() {
            this.playerLoading = true;
            try {
                const res = await fetch(`/api/player/resolve?id=${this.playerId}`);
                const data = await res.json();
                if (data.success) {
                    this.playerValid = true;
                    this.playerName = data.data.playerName;
                } else {
                    this.playerValid = false;
                    this.playerError = data.error || 'Oyuncu bulunamadı';
                }
            } catch (e) {
                this.playerValid = false;
                this.playerError = 'Doğrulama hatası';
            }
            this.playerLoading = false;
        },
        
        proceedToPayment() {
            const isAuth = this.$root.__x.$data.isAuthenticated;
            if (!isAuth) {
                this.step = 2;
                return;
            }
            this.createOrder();
        },
        
        async createOrder() {
            this.step = 3;
            const token = localStorage.getItem('userToken');
            
            try {
                const res = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        productId: this.$root.__x.$data.selectedProduct?.id,
                        playerId: this.playerId,
                        playerName: this.playerName
                    })
                });
                const data = await res.json();
                
                if (data.success && data.data.paymentUrl) {
                    // Redirect to Shopier payment
                    window.location.href = data.data.paymentUrl;
                } else {
                    this.$root.__x.$data.showToast(data.error || 'Sipariş oluşturulamadı', 'error');
                    this.step = 1;
                }
            } catch (e) {
                this.$root.__x.$data.showToast('Bir hata oluştu', 'error');
                this.step = 1;
            }
        },
        
        reset() {
            this.step = 1;
            this.playerId = '';
            this.playerName = '';
            this.playerValid = null;
            this.playerError = '';
        }
    }
}
</script>