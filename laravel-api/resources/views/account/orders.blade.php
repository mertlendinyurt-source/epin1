@extends('layouts.main')

@section('title', 'Siparişlerim')

@section('content')
<div x-data="ordersPage()" x-init="init()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-white">Siparişlerim</h1>
                </div>
                
                <!-- Orders List -->
                <div class="bg-[#12151a] rounded-xl border border-white/5 overflow-hidden">
                    <div x-show="loading" class="p-8 text-center">
                        <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                    
                    <div x-show="!loading" class="divide-y divide-white/5">
                        <template x-for="order in orders" :key="order.id">
                            <a :href="'/account/orders/' + order.id" class="block px-6 py-4 hover:bg-white/5 transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-900/40 to-purple-900/40 flex items-center justify-center">
                                            <i data-lucide="coins" class="w-6 h-6 text-yellow-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-white" x-text="order.productTitle"></p>
                                            <p class="text-sm text-zinc-400">
                                                <span x-text="order.ucAmount + ' UC'"></span> • 
                                                <span x-text="'Oyuncu: ' + order.playerName"></span>
                                            </p>
                                            <p class="text-xs text-zinc-500" x-text="new Date(order.createdAt).toLocaleString('tr-TR')"></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-white" x-text="order.amount.toFixed(2) + ' ₺'"></p>
                                        <span :class="['text-xs px-2 py-1 rounded-full', getStatusClass(order.status)]" x-text="getStatusText(order.status)"></span>
                                        <template x-if="order.delivery?.status === 'delivered'">
                                            <span class="ml-1 text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400">Teslim Edildi</span>
                                        </template>
                                    </div>
                                </div>
                            </a>
                        </template>
                        
                        <div x-show="orders.length === 0 && !loading" class="px-6 py-12 text-center">
                            <i data-lucide="shopping-bag" class="w-16 h-16 mx-auto text-zinc-600 mb-4"></i>
                            <p class="text-zinc-400">Henüz siparişiniz yok.</p>
                            <a href="/" class="inline-block mt-4 btn-primary px-6 py-2 rounded-lg text-sm">Alışverişe Başla</a>
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
function ordersPage() {
    return {
        orders: [],
        loading: true,
        
        init() {
            this.fetchOrders();
        },
        
        async fetchOrders() {
            const token = localStorage.getItem('userToken');
            if (!token) { this.loading = false; return; }
            
            try {
                const res = await fetch('/api/account/orders', { headers: { Authorization: `Bearer ${token}` } });
                const data = await res.json();
                if (data.success) this.orders = data.data;
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        getStatusClass(status) {
            const classes = { 'pending': 'bg-yellow-500/20 text-yellow-400', 'paid': 'bg-green-500/20 text-green-400', 'failed': 'bg-red-500/20 text-red-400', 'refunded': 'bg-zinc-500/20 text-zinc-400' };
            return classes[status] || 'bg-zinc-500/20 text-zinc-400';
        },
        
        getStatusText(status) {
            const texts = { 'pending': 'Bekliyor', 'paid': 'Ödendi', 'failed': 'Başarısız', 'refunded': 'İade' };
            return texts[status] || status;
        }
    }
}
</script>
@endpush