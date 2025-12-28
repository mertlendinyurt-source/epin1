@extends('layouts.main')

@section('title', 'Sipariş Detayı')

@section('content')
<div x-data="orderDetailPage()" x-init="init()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <a href="/account/orders" class="inline-flex items-center text-zinc-400 hover:text-white mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>Siparişlerime Dön
                </a>
                
                <div x-show="loading" class="bg-[#12151a] rounded-xl border border-white/5 p-8 text-center">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                
                <div x-show="!loading && order" x-cloak>
                    <!-- Order Header -->
                    <div class="bg-[#12151a] rounded-xl border border-white/5 p-6 mb-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h1 class="text-xl font-bold text-white" x-text="order.productTitle"></h1>
                                <p class="text-sm text-zinc-400">Sipariş #<span x-text="order.id.slice(-8).toUpperCase()"></span></p>
                            </div>
                            <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusClass(order.status)]" x-text="getStatusText(order.status)"></span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-zinc-400">UC Miktarı</p>
                                <p class="font-semibold text-white" x-text="order.ucAmount + ' UC'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-400">Tutar</p>
                                <p class="font-semibold text-white" x-text="order.amount.toFixed(2) + ' ₺'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-400">Oyuncu ID</p>
                                <p class="font-semibold text-white" x-text="order.playerId"></p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-400">Oyuncu Adı</p>
                                <p class="font-semibold text-white" x-text="order.playerName"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Status -->
                    <div class="bg-[#12151a] rounded-xl border border-white/5 p-6 mb-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Teslimat Durumu</h2>
                        
                        <div x-show="order.delivery?.status === 'delivered'" class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                <i data-lucide="check-circle" class="w-6 h-6 text-green-500"></i>
                                <span class="font-semibold text-green-400">Teslim Edildi</span>
                            </div>
                            
                            <!-- UC Codes -->
                            <template x-if="order.delivery?.items?.length > 0">
                                <div>
                                    <p class="text-sm text-zinc-400 mb-2">UC Kodunuz:</p>
                                    <template x-for="code in order.delivery.items" :key="code">
                                        <div class="bg-black/30 rounded-lg p-3 mb-2 border border-dashed border-blue-500/30">
                                            <code class="text-blue-400 font-mono text-lg tracking-wider" x-text="code"></code>
                                        </div>
                                    </template>
                                    <p class="text-xs text-red-400 mt-3">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 inline mr-1"></i>
                                        Bu kodları kimseyle paylaşmayın!
                                    </p>
                                </div>
                            </template>
                        </div>
                        
                        <div x-show="order.delivery?.status === 'pending'" class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="clock" class="w-6 h-6 text-yellow-500"></i>
                                <div>
                                    <span class="font-semibold text-yellow-400">Hazırlanıyor</span>
                                    <p class="text-sm text-zinc-400">Siparişiniz en kısa sürede teslim edilecek.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div x-show="order.delivery?.status === 'hold' || order.userDeliveryStatus === 'review'" class="bg-orange-500/10 border border-orange-500/20 rounded-lg p-4">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="eye" class="w-6 h-6 text-orange-500"></i>
                                <div>
                                    <span class="font-semibold text-orange-400">İnceleniyor</span>
                                    <p class="text-sm text-zinc-400">Siparişiniz kontrol aşamasındadır.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Timeline -->
                    <div class="bg-[#12151a] rounded-xl border border-white/5 p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Sipariş Geçmişi</h2>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center shrink-0">
                                    <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-white">Sipariş Oluşturuldu</p>
                                    <p class="text-sm text-zinc-400" x-text="new Date(order.createdAt).toLocaleString('tr-TR')"></p>
                                </div>
                            </div>
                            <div x-show="order.status === 'paid'" class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center shrink-0">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-white">Ödeme Alındı</p>
                                    <p class="text-sm text-zinc-400" x-text="order.paidAt ? new Date(order.paidAt).toLocaleString('tr-TR') : ''"></p>
                                </div>
                            </div>
                            <div x-show="order.delivery?.status === 'delivered'" class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center shrink-0">
                                    <i data-lucide="package-check" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-white">Teslim Edildi</p>
                                    <p class="text-sm text-zinc-400" x-text="order.delivery?.deliveredAt ? new Date(order.delivery.deliveredAt).toLocaleString('tr-TR') : ''"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function orderDetailPage() {
    return {
        order: null,
        loading: true,
        orderId: '{{ $orderId }}',
        
        init() {
            this.fetchOrder();
        },
        
        async fetchOrder() {
            const token = localStorage.getItem('userToken');
            if (!token) { this.loading = false; return; }
            
            try {
                const res = await fetch(`/api/account/orders/${this.orderId}`, { headers: { Authorization: `Bearer ${token}` } });
                const data = await res.json();
                if (data.success) this.order = data.data.order;
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        getStatusClass(status) {
            const classes = { 'pending': 'bg-yellow-500/20 text-yellow-400', 'paid': 'bg-green-500/20 text-green-400', 'failed': 'bg-red-500/20 text-red-400', 'refunded': 'bg-zinc-500/20 text-zinc-400' };
            return classes[status] || 'bg-zinc-500/20 text-zinc-400';
        },
        
        getStatusText(status) {
            const texts = { 'pending': 'Ödeme Bekliyor', 'paid': 'Ödendi', 'failed': 'Başarısız', 'refunded': 'İade Edildi' };
            return texts[status] || status;
        }
    }
}
</script>
@endpush