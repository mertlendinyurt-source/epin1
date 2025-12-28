@extends('layouts.main')

@section('title', 'Destek Talebi Detayı')

@section('content')
<div x-data="ticketDetailPage()" x-init="init()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <a href="/account/support" class="inline-flex items-center text-zinc-400 hover:text-white mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>Destek Taleplerine Dön
                </a>
                
                <div x-show="loading" class="bg-[#12151a] rounded-xl border border-white/5 p-8 text-center">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                
                <div x-show="!loading && ticket" x-cloak>
                    <!-- Ticket Header -->
                    <div class="bg-[#12151a] rounded-xl border border-white/5 p-6 mb-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-xl font-bold text-white" x-text="ticket.subject"></h1>
                                <p class="text-sm text-zinc-400 mt-1">
                                    #<span x-text="ticket.id.slice(-8).toUpperCase()"></span> • 
                                    <span x-text="getCategoryText(ticket.category)"></span>
                                </p>
                            </div>
                            <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusClass(ticket.status)]" x-text="getStatusText(ticket.status)"></span>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <div class="bg-[#12151a] rounded-xl border border-white/5 overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-white/5">
                            <h2 class="font-semibold text-white">Mesajlar</h2>
                        </div>
                        <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                            <template x-for="msg in ticket.messages" :key="msg.id">
                                <div :class="['p-4 rounded-lg', msg.sender === 'user' ? 'bg-blue-500/10 border border-blue-500/20 ml-8' : 'bg-[#1a1d24] border border-white/5 mr-8']">
                                    <div class="flex items-center justify-between mb-2">
                                        <span :class="['text-sm font-medium', msg.sender === 'user' ? 'text-blue-400' : 'text-green-400']" x-text="msg.sender === 'user' ? 'Siz' : 'Destek Ekibi'"></span>
                                        <span class="text-xs text-zinc-500" x-text="new Date(msg.createdAt).toLocaleString('tr-TR')"></span>
                                    </div>
                                    <p class="text-zinc-300 whitespace-pre-wrap" x-text="msg.message"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Reply Form -->
                    <div x-show="ticket.status !== 'closed'" class="bg-[#12151a] rounded-xl border border-white/5 p-6">
                        <template x-if="ticket.userCanReply">
                            <form @submit.prevent="sendReply">
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Yanıtınız</label>
                                <textarea x-model="replyMessage" required minlength="2" maxlength="2000" rows="4" class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition resize-none" placeholder="Mesajınızı yazın..."></textarea>
                                <button type="submit" :disabled="sending" class="mt-4 btn-primary px-6 py-2 rounded-lg font-medium disabled:opacity-50">
                                    <span x-show="!sending">Gönder</span>
                                    <span x-show="sending">Gönderiliyor...</span>
                                </button>
                            </form>
                        </template>
                        <template x-if="!ticket.userCanReply">
                            <div class="text-center py-4">
                                <i data-lucide="clock" class="w-8 h-8 mx-auto text-yellow-500 mb-2"></i>
                                <p class="text-zinc-400">Admin yanıtı bekleniyor. Yanıt geldiğinde mesaj gönderebilirsiniz.</p>
                            </div>
                        </template>
                    </div>
                    
                    <div x-show="ticket.status === 'closed'" class="bg-zinc-500/10 rounded-xl border border-zinc-500/20 p-6 text-center">
                        <i data-lucide="check-circle" class="w-8 h-8 mx-auto text-zinc-500 mb-2"></i>
                        <p class="text-zinc-400">Bu talep kapatılmıştır.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ticketDetailPage() {
    return {
        ticket: null,
        loading: true,
        ticketId: '{{ $ticketId }}',
        replyMessage: '',
        sending: false,
        
        init() {
            this.fetchTicket();
        },
        
        async fetchTicket() {
            const token = localStorage.getItem('userToken');
            if (!token) { this.loading = false; return; }
            
            try {
                const res = await fetch(`/api/support/tickets/${this.ticketId}`, { headers: { Authorization: `Bearer ${token}` } });
                const data = await res.json();
                if (data.success) this.ticket = data.data;
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        async sendReply() {
            this.sending = true;
            const token = localStorage.getItem('userToken');
            
            try {
                const res = await fetch(`/api/support/tickets/${this.ticketId}/messages`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
                    body: JSON.stringify({ message: this.replyMessage })
                });
                const data = await res.json();
                if (data.success) {
                    this.replyMessage = '';
                    this.fetchTicket();
                }
            } catch (e) { console.error(e); }
            this.sending = false;
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