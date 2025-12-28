@extends('layouts.main')

@section('title', 'Profil')

@section('content')
<div x-data="profilePage()" x-init="init()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-white mb-6">Profil Bilgileri</h1>
                
                <div class="bg-[#12151a] rounded-xl border border-white/5 p-6">
                    <form @submit.prevent="saveProfile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Ad</label>
                                <input type="text" x-model="form.firstName" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Soyad</label>
                                <input type="text" x-model="form.lastName" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">E-posta</label>
                                <input type="email" :value="user?.email" disabled class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-zinc-500 cursor-not-allowed">
                                <p class="text-xs text-zinc-500 mt-1">E-posta adresi değiştirilemez</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Telefon</label>
                                <input type="tel" x-model="form.phone" class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition" placeholder="5XX XXX XX XX">
                            </div>
                        </div>
                        
                        <p x-show="message" :class="['mt-4 text-sm', success ? 'text-green-400' : 'text-red-400']" x-text="message"></p>
                        
                        <button type="submit" :disabled="saving" class="mt-6 btn-primary px-6 py-3 rounded-lg font-medium disabled:opacity-50">
                            <span x-show="!saving">Kaydet</span>
                            <span x-show="saving">Kaydediliyor...</span>
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
function profilePage() {
    return {
        form: { firstName: '', lastName: '', phone: '' },
        saving: false,
        message: '',
        success: false,
        
        init() {
            this.$watch('$root.__x.$data.user', (user) => {
                if (user) {
                    this.form.firstName = user.firstName || '';
                    this.form.lastName = user.lastName || '';
                    this.form.phone = user.phone || '';
                }
            }, { immediate: true });
        },
        
        async saveProfile() {
            this.saving = true;
            this.message = '';
            const token = localStorage.getItem('userToken');
            
            try {
                const res = await fetch('/api/account/profile', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    this.success = true;
                    this.message = 'Profil güncellendi!';
                    localStorage.setItem('userData', JSON.stringify(data.data));
                    this.$root.__x.$data.user = data.data;
                } else {
                    this.success = false;
                    this.message = data.error || 'Güncelleme başarısız';
                }
            } catch (e) {
                this.success = false;
                this.message = 'Bir hata oluştu';
            }
            this.saving = false;
        }
    }
}
</script>
@endpush