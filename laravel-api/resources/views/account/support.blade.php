@extends('layouts.main')

@section('title', 'Destek Talepleri')

@section('content')
<div x-data="supportPage()" x-init="init()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-white">Destek Talepleri</h1>
                    <a href="/account/support/new" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i>Yeni Talep
                    </a>
                </div>
                
                <div class="bg-[#12151a] rounded-xl border border-white/5 overflow-hidden">
                    <div x-show="loading" class="p-8 text-center">
                        <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                    
                    <div x-show="!loading" class="divide-y divide-white/5">
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <a :href="'/account/support/' + ticket.id" class="block px-6 py-4 hover:bg-white/5 transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-white" x-text="ticket.subject"></p>
                                        <p class="text-sm text-zinc-400">
                                            <span x-text="getCategoryText(ticket.category)"></span> • 
                                            <span x-text="new Date(ticket.createdAt).toLocaleDateString('tr-TR')"></span>
                                        </p>
                                    </div>
                                    <span :class="['text-xs px-2 py-1 rounded-full', getStatusClass(ticket.status)]" x-text="getStatusText(ticket.status)"></span>
                                </div>
                            </a>
                        </template>
                        
                        <div x-show="tickets.length === 0 && !loading" class="px-6 py-12 text-center">
                            <i data-lucide="message-circle" class="w-16 h-16 mx-auto text-zinc-600 mb-4"></i>
                            <p class="text-zinc-400">Henüz destek talebiniz yok.</p>
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
function supportPage() {
    return {
        tickets: [],
        loading: true,
        
        init() {
            this.fetchTickets();
        },
        
        async fetchTickets() {
            const token = localStorage.getItem('userToken');
            if (!token) { this.loading = false; return; }
            
            try {
                const res = await fetch('/api/support/tickets', { headers: { Authorization: `Bearer ${token}` } });
                const data = await res.json();
                if (data.success) this.tickets = data.data;
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        getStatusClass(status) {
            const classes = { 'waiting_admin': 'bg-yellow-500/20 text-yellow-400', 'waiting_user': 'bg-blue-500/20 text-blue-400', 'closed': 'bg-zinc-500/20 text-zinc-400' };
            return classes[status] || 'bg-zinc-500/20 text-zinc-400';
        },
        
        getStatusText(status) {
            const texts = { 'waiting_admin': 'Yanıt Bekleniyor', 'waiting_user': 'Yanıtınız Bekleniyor', 'closed': 'Kapatıldı' };
            return texts[status] || status;
        },
        
        getCategoryText(category) {
            const texts = { 'odeme': 'Ödeme', 'teslimat': 'Teslimat', 'hesap': 'Hesap', 'diger': 'Diğer' };
            return texts[category] || category;
        }
    }
}
</script>
@endpush