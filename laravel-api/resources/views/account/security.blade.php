@extends('layouts.main')

@section('title', 'Güvenlik')

@section('content')
<div x-data="securityPage()">
    @include('partials.header')
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            @include('account.partials.sidebar')
            
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-white mb-6">Güvenlik Ayarları</h1>
                
                <!-- Password Change -->
                <div class="bg-[#12151a] rounded-xl border border-white/5 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Şifre Değiştir</h2>
                    <form @submit.prevent="changePassword">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Mevcut Şifre</label>
                                <input type="password" x-model="form.currentPassword" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Yeni Şifre</label>
                                <input type="password" x-model="form.newPassword" required minlength="6" class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Yeni Şifre (Tekrar)</label>
                                <input type="password" x-model="form.confirmPassword" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                        
                        <p x-show="message" :class="['mt-4 text-sm', success ? 'text-green-400' : 'text-red-400']" x-text="message"></p>
                        
                        <button type="submit" :disabled="saving" class="mt-6 btn-primary px-6 py-3 rounded-lg font-medium disabled:opacity-50">
                            <span x-show="!saving">Şifreyi Değiştir</span>
                            <span x-show="saving">Değiştiriliyor...</span>
                        </button>
                    </form>
                </div>
                
                <!-- Delete Account -->
                <div class="bg-[#12151a] rounded-xl border border-red-500/20 p-6">
                    <h2 class="text-lg font-semibold text-red-400 mb-2">Hesabı Sil</h2>
                    <p class="text-sm text-zinc-400 mb-4">Hesabınızı sildiğinizde tüm verileriniz kalıcı olarak silinir.</p>
                    <button @click="confirmDelete = true" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                        Hesabımı Sil
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-backdrop" @click="confirmDelete = false"></div>
        <div class="relative bg-[#12151a] rounded-xl border border-white/10 p-6 max-w-md w-full" x-transition>
            <h3 class="text-lg font-semibold text-white mb-4">Hesabınızı silmek istediğinizden emin misiniz?</h3>
            <p class="text-sm text-zinc-400 mb-6">Bu işlem geri alınamaz. Tüm siparişleriniz ve verileriniz silinecektir.</p>
            <div class="flex space-x-3">
                <button @click="confirmDelete = false" class="flex-1 py-2 bg-zinc-700 text-white rounded-lg hover:bg-zinc-600 transition">İptal</button>
                <button @click="deleteAccount()" class="flex-1 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Evet, Sil</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function securityPage() {
    return {
        form: { currentPassword: '', newPassword: '', confirmPassword: '' },
        saving: false,
        message: '',
        success: false,
        confirmDelete: false,
        
        async changePassword() {
            if (this.form.newPassword !== this.form.confirmPassword) {
                this.message = 'Şifreler eşleşmiyor';
                this.success = false;
                return;
            }
            
            this.saving = true;
            this.message = '';
            const token = localStorage.getItem('userToken');
            
            try {
                const res = await fetch('/api/account/password', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    this.success = true;
                    this.message = 'Şifre başarıyla değiştirildi!';
                    this.form = { currentPassword: '', newPassword: '', confirmPassword: '' };
                } else {
                    this.success = false;
                    this.message = data.error || 'Şifre değiştirilemedi';
                }
            } catch (e) {
                this.success = false;
                this.message = 'Bir hata oluştu';
            }
            this.saving = false;
        },
        
        async deleteAccount() {
            const token = localStorage.getItem('userToken');
            try {
                const res = await fetch('/api/account/me', {
                    method: 'DELETE',
                    headers: { Authorization: `Bearer ${token}` }
                });
                const data = await res.json();
                if (data.success) {
                    localStorage.removeItem('userToken');
                    localStorage.removeItem('userData');
                    window.location.href = '/';
                } else {
                    alert(data.error || 'Hesap silinemedi');
                }
            } catch (e) {
                alert('Bir hata oluştu');
            }
            this.confirmDelete = false;
        }
    }
}
</script>
@endpush