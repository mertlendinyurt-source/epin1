@extends('layouts.main')

@section('title', 'Hesabım')

@section('content')
<div x-data="accountPage()" x-init="init()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar -->
            @include('account.partials.sidebar')
            
            <!-- Content -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-white mb-6">Hesabım</h1>
                
                <!-- Auth Check -->
                <div x-show="!isAuthenticated" class="bg-[#12151a] rounded-xl border border-white/5 p-8 text-center">
                    <i data-lucide="user-circle" class="w-16 h-16 mx-auto text-zinc-600 mb-4"></i>
                    <h2 class="text-lg font-semibold text-white mb-2">Giriş Yapın</h2>
                    <p class="text-zinc-400 mb-6">Hesabınızı görüntülemek için giriş yapmalısınız.</p>
                    <button @click="$root.__x.$data.openAuthModal('login')" class="btn-primary px-6 py-3 rounded-lg font-medium">
                        Giriş Yap
                    </button>
                </div>
                
                <!-- Dashboard -->
                <div x-show="isAuthenticated" x-cloak>
                    <!-- Welcome -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-6 mb-6">
                        <h2 class="text-xl font-bold text-white mb-1">Hoş geldin, <span x-text="user?.firstName"></span>!</h2>
                        <p class="text-blue-100">Hesabını yönet ve siparişlerini takip et.</p>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-[#12151a] rounded-xl border border-white/5 p-4">
                            <i data-lucide="shopping-bag" class="w-8 h-8 text-blue-400 mb-2"></i>
                            <p class="text-2xl font-bold text-white" x-text="stats.totalOrders">0</p>
                            <p class="text-sm text-zinc-400">Toplam Sipariş</p>
                        </div>
                        <div class="bg-[#12151a] rounded-xl border border-white/5 p-4">
                            <i data-lucide="check-circle" class="w-8 h-8 text-green-400 mb-2"></i>
                            <p class="text-2xl font-bold text-white" x-text="stats.completedOrders">0</p>
                            <p class="text-sm text-zinc-400">Tamamlanan</p>
                        </div>
                        <div class="bg-[#12151a] rounded-xl border border-white/5 p-4">
                            <i data-lucide="clock" class="w-8 h-8 text-yellow-400 mb-2"></i>
                            <p class="text-2xl font-bold text-white" x-text="stats.pendingOrders">0</p>
                            <p class="text-sm text-zinc-400">Bekleyen</p>
                        </div>
                        <div class="bg-[#12151a] rounded-xl border border-white/5 p-4">
                            <i data-lucide="message-circle" class="w-8 h-8 text-purple-400 mb-2"></i>
                            <p class="text-2xl font-bold text-white" x-text="stats.openTickets">0</p>
                            <p class="text-sm text-zinc-400">Açık Talepler</p>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="bg-[#12151a] rounded-xl border border-white/5 overflow-hidden">
                        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                            <h3 class="font-semibold text-white">Son Siparişler</h3>
                            <a href="/account/orders" class="text-sm text-blue-400 hover:text-blue-300">Tümünü Gör</a>
                        </div>
                        <div class="divide-y divide-white/5">
                            <template x-for="order in recentOrders" :key="order.id">
                                <a :href="'/account/orders/' + order.id" class="block px-6 py-4 hover:bg-white/5 transition">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-white" x-text="order.productTitle"></p>
                                            <p class="text-sm text-zinc-400" x-text="new Date(order.createdAt).toLocaleDateString('tr-TR')"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-white" x-text="order.amount + ' ₺'"></p>
                                            <span :class="['text-xs px-2 py-1 rounded-full', getStatusClass(order.status)]" x-text="getStatusText(order.status)"></span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                            <div x-show="recentOrders.length === 0" class="px-6 py-8 text-center text-zinc-500">
                                Henüz siparişiniz yok.
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
function accountPage() {
    return {
        stats: { totalOrders: 0, completedOrders: 0, pendingOrders: 0, openTickets: 0 },
        recentOrders: [],
        
        init() {
            if (this.$root.__x.$data.isAuthenticated) {
                this.fetchData();
            }
            this.$watch('$root.__x.$data.isAuthenticated', (val) => {
                if (val) this.fetchData();
            });
        },
        
        async fetchData() {
            const token = localStorage.getItem('userToken');
            if (!token) return;
            
            try {
                const [ordersRes, ticketsRes] = await Promise.all([
                    fetch('/api/account/orders', { headers: { Authorization: `Bearer ${token}` } }),
                    fetch('/api/support/tickets', { headers: { Authorization: `Bearer ${token}` } })
                ]);
                
                const ordersData = await ordersRes.json();
                const ticketsData = await ticketsRes.json();
                
                if (ordersData.success) {
                    const orders = ordersData.data;
                    this.stats.totalOrders = orders.length;
                    this.stats.completedOrders = orders.filter(o => o.status === 'paid' && o.delivery?.status === 'delivered').length;
                    this.stats.pendingOrders = orders.filter(o => o.status === 'pending' || (o.status === 'paid' && o.delivery?.status !== 'delivered')).length;
                    this.recentOrders = orders.slice(0, 5);
                }
                
                if (ticketsData.success) {
                    this.stats.openTickets = ticketsData.data.filter(t => t.status !== 'closed').length;
                }
            } catch (e) { console.error(e); }
        },
        
        getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-500/20 text-yellow-400',
                'paid': 'bg-green-500/20 text-green-400',
                'failed': 'bg-red-500/20 text-red-400',
                'refunded': 'bg-zinc-500/20 text-zinc-400'
            };
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