@extends('layouts.main')

@section('title', 'Yeni Destek Talebi')

@section('content')
<div x-data="newTicketPage()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <a href="/account/support" class="inline-flex items-center text-zinc-400 hover:text-white mb-6">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>Destek Taleplerine Dön
                </a>
                
                <h1 class="text-2xl font-bold text-white mb-6">Yeni Destek Talebi</h1>
                
                <div class="bg-[#12151a] rounded-xl border border-white/5 p-6">
                    <form @submit.prevent="createTicket">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Kategori</label>
                                <select x-model="form.category" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition">
                                    <option value="">Seçiniz</option>
                                    <option value="odeme">Ödeme Sorunu</option>
                                    <option value="teslimat">Teslimat Sorunu</option>
                                    <option value="hesap">Hesap Sorunu</option>
                                    <option value="diger">Diğer</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Konu</label>
                                <input type="text" x-model="form.subject" required minlength="5" maxlength="200" class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition" placeholder="Sorununuzu kısaca özetleyin">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Mesaj</label>
                                <textarea x-model="form.message" required minlength="10" maxlength="2000" rows="6" class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition resize-none" placeholder="Sorununuzu detaylı açıklayın..."></textarea>
                            </div>
                        </div>
                        
                        <p x-show="error" class="mt-4 text-sm text-red-400" x-text="error"></p>
                        
                        <button type="submit" :disabled="submitting" class="mt-6 btn-primary px-6 py-3 rounded-lg font-medium disabled:opacity-50">
                            <span x-show="!submitting">Talebi Gönder</span>
                            <span x-show="submitting">Gönderiliyor...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function newTicketPage() {
    return {
        form: { category: '', subject: '', message: '' },
        submitting: false,
        error: '',
        
        async createTicket() {
            this.submitting = true;
            this.error = '';
            const token = localStorage.getItem('userToken');
            
            try {
                const res = await fetch('/api/support/tickets', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '/account/support/' + data.data.id;
                } else {
                    this.error = data.error || 'Talep oluşturulamadı';
                }
            } catch (e) {
                this.error = 'Bir hata oluştu';
            }
            this.submitting = false;
        }
    }
}
</script>
@endpush