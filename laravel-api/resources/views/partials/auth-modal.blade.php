<!-- Auth Modal -->
<div x-show="authModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 modal-backdrop" @click="authModalOpen = false"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-[#12151a] rounded-2xl shadow-2xl w-full max-w-md border border-white/10 overflow-hidden" x-transition>
        <!-- Close Button -->
        <button @click="authModalOpen = false" class="absolute top-4 right-4 text-zinc-400 hover:text-white z-10">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        
        <!-- Tabs -->
        <div class="flex border-b border-white/10">
            <button @click="authTab = 'login'" :class="['flex-1 py-4 text-sm font-medium transition', authTab === 'login' ? 'text-white border-b-2 border-blue-500' : 'text-zinc-400 hover:text-white']">
                Giriş Yap
            </button>
            <button @click="authTab = 'register'" :class="['flex-1 py-4 text-sm font-medium transition', authTab === 'register' ? 'text-white border-b-2 border-blue-500' : 'text-zinc-400 hover:text-white']">
                Kayıt Ol
            </button>
        </div>
        
        <!-- Login Form -->
        <div x-show="authTab === 'login'" class="p-6" x-data="loginForm()">
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">E-posta</label>
                        <input type="email" x-model="form.email" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" placeholder="ornek@email.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Şifre</label>
                        <input type="password" x-model="form.password" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" placeholder="••••••••">
                    </div>
                </div>
                
                <p x-show="error" x-text="error" class="mt-3 text-sm text-red-400"></p>
                
                <button type="submit" :disabled="loading" class="w-full mt-6 py-3 btn-primary text-white font-medium rounded-lg disabled:opacity-50">
                    <span x-show="!loading">Giriş Yap</span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Giriş yapılıyor...
                    </span>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-white/10"></div></div>
                <div class="relative flex justify-center text-sm"><span class="px-4 bg-[#12151a] text-zinc-500">veya</span></div>
            </div>
            
            <!-- Google Login -->
            <button @click="googleLogin()" class="w-full py-3 bg-white text-gray-800 font-medium rounded-lg flex items-center justify-center space-x-2 hover:bg-gray-100 transition">
                <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                <span>Google ile Giriş</span>
            </button>
        </div>
        
        <!-- Register Form -->
        <div x-show="authTab === 'register'" class="p-6" x-data="registerForm()">
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-1">Ad</label>
                            <input type="text" x-model="form.firstName" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 outline-none transition" placeholder="Adınız">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-1">Soyad</label>
                            <input type="text" x-model="form.lastName" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 outline-none transition" placeholder="Soyadınız">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">E-posta</label>
                        <input type="email" x-model="form.email" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 outline-none transition" placeholder="ornek@email.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Telefon</label>
                        <input type="tel" x-model="form.phone" required class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 outline-none transition" placeholder="5XX XXX XX XX">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Şifre</label>
                        <input type="password" x-model="form.password" required minlength="6" class="w-full px-4 py-3 bg-[#1a1d24] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:border-blue-500 outline-none transition" placeholder="En az 6 karakter">
                    </div>
                </div>
                
                <p x-show="error" x-text="error" class="mt-3 text-sm text-red-400"></p>
                
                <button type="submit" :disabled="loading" class="w-full mt-6 py-3 btn-primary text-white font-medium rounded-lg disabled:opacity-50">
                    <span x-show="!loading">Kayıt Ol</span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Kayıt olunuyor...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function loginForm() {
    return {
        form: { email: '', password: '' },
        loading: false,
        error: '',
        async submit() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    localStorage.setItem('userToken', data.data.token);
                    localStorage.setItem('userData', JSON.stringify(data.data.user));
                    this.$root.__x.$data.isAuthenticated = true;
                    this.$root.__x.$data.user = data.data.user;
                    this.$root.__x.$data.authModalOpen = false;
                    this.$root.__x.$data.showToast('Giriş başarılı!');
                    window.location.reload();
                } else {
                    this.error = data.error || 'Giriş başarısız';
                }
            } catch (e) {
                this.error = 'Bir hata oluştu';
            }
            this.loading = false;
        },
        async googleLogin() {
            try {
                const res = await fetch('/api/auth/google');
                const data = await res.json();
                if (data.success && data.data.authUrl) {
                    window.location.href = data.data.authUrl;
                } else {
                    this.$root.__x.$data.showToast(data.error || 'Google giriş hatası', 'error');
                }
            } catch (e) {
                this.$root.__x.$data.showToast('Google giriş başlatılamadı', 'error');
            }
        }
    }
}

function registerForm() {
    return {
        form: { firstName: '', lastName: '', email: '', phone: '', password: '' },
        loading: false,
        error: '',
        async submit() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    localStorage.setItem('userToken', data.data.token);
                    localStorage.setItem('userData', JSON.stringify(data.data.user));
                    this.$root.__x.$data.isAuthenticated = true;
                    this.$root.__x.$data.user = data.data.user;
                    this.$root.__x.$data.authModalOpen = false;
                    this.$root.__x.$data.showToast('Kayıt başarılı! Hoş geldiniz!');
                    window.location.reload();
                } else {
                    this.error = data.error || 'Kayıt başarısız';
                }
            } catch (e) {
                this.error = 'Bir hata oluştu';
            }
            this.loading = false;
        }
    }
}
</script>