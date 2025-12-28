<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $siteSettings['siteName'] ?? 'PINLY') - @yield('page_title', 'Dijital Kod ve Oyun Satış Platformu')</title>
    <meta name="description" content="@yield('description', $siteSettings['metaDescription'] ?? 'PUBG Mobile UC satın al. Güvenilir, hızlı ve uygun fiyatlı UC satış platformu.')">
    
    @if(isset($siteSettings['favicon']) && $siteSettings['favicon'])
    <link rel="icon" href="{{ $siteSettings['favicon'] }}">
    @endif
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        border: 'hsl(240 3.7% 15.9%)',
                        input: 'hsl(240 3.7% 15.9%)',
                        ring: 'hsl(240 4.9% 83.9%)',
                        background: 'hsl(240 10% 3.9%)',
                        foreground: 'hsl(0 0% 98%)',
                        primary: { DEFAULT: 'hsl(0 0% 98%)', foreground: 'hsl(240 5.9% 10%)' },
                        secondary: { DEFAULT: 'hsl(240 3.7% 15.9%)', foreground: 'hsl(0 0% 98%)' },
                        destructive: { DEFAULT: 'hsl(0 62.8% 30.6%)', foreground: 'hsl(0 0% 98%)' },
                        muted: { DEFAULT: 'hsl(240 3.7% 15.9%)', foreground: 'hsl(240 5% 64.9%)' },
                        accent: { DEFAULT: 'hsl(240 3.7% 15.9%)', foreground: 'hsl(0 0% 98%)' },
                        card: { DEFAULT: 'hsl(240 10% 3.9%)', foreground: 'hsl(0 0% 98%)' },
                    },
                    borderRadius: { lg: '0.5rem', md: 'calc(0.5rem - 2px)', sm: 'calc(0.5rem - 4px)' },
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Toast styles */
        .toast { position: fixed; bottom: 1rem; right: 1rem; z-index: 9999; }
        .toast-content { padding: 1rem 1.5rem; border-radius: 0.5rem; color: white; font-weight: 500; animation: slideIn 0.3s ease; }
        .toast-success { background: linear-gradient(135deg, #10b981, #059669); }
        .toast-error { background: linear-gradient(135deg, #ef4444, #dc2626); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        /* Modal backdrop */
        .modal-backdrop { background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(4px); }
        
        /* Button hover effects */
        .btn-primary { background: linear-gradient(135deg, #2563eb, #3b82f6); transition: all 0.2s; }
        .btn-primary:hover { background: linear-gradient(135deg, #1d4ed8, #2563eb); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
        
        /* Card hover */
        .product-card { transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3); }
        
        /* Gradient text */
        .gradient-text { background: linear-gradient(135deg, #60a5fa, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-[#0a0b0d] text-white min-h-screen antialiased" x-data="mainApp()">
    <!-- Toast Container -->
    <div id="toast-container" class="toast" x-show="toast.show" x-cloak x-transition>
        <div :class="['toast-content', toast.type === 'success' ? 'toast-success' : 'toast-error']" x-text="toast.message"></div>
    </div>

    @yield('content')
    
    <!-- Footer -->
    @include('partials.footer')
    
    <!-- Auth Modal -->
    @include('partials.auth-modal')
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Main Alpine.js App
        function mainApp() {
            return {
                // Toast
                toast: { show: false, message: '', type: 'success' },
                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 3000);
                },
                
                // Auth
                isAuthenticated: false,
                user: null,
                authModalOpen: false,
                authTab: 'login',
                
                // Site settings from PHP
                siteSettings: @json($siteSettings ?? []),
                
                init() {
                    this.checkAuth();
                    this.handleGoogleCallback();
                },
                
                checkAuth() {
                    const token = localStorage.getItem('userToken');
                    const userData = localStorage.getItem('userData');
                    if (token && userData) {
                        this.isAuthenticated = true;
                        this.user = JSON.parse(userData);
                    }
                },
                
                handleGoogleCallback() {
                    const params = new URLSearchParams(window.location.search);
                    if (params.get('google_auth') === 'success') {
                        const getCookie = (name) => {
                            const value = `; ${document.cookie}`;
                            const parts = value.split(`; ${name}=`);
                            if (parts.length === 2) return parts.pop().split(';').shift();
                            return null;
                        };
                        
                        const token = getCookie('googleAuthToken');
                        const userDataEncoded = getCookie('googleAuthUser');
                        
                        if (token && userDataEncoded) {
                            try {
                                const userData = JSON.parse(decodeURIComponent(userDataEncoded));
                                localStorage.setItem('userToken', token);
                                localStorage.setItem('userData', JSON.stringify(userData));
                                this.isAuthenticated = true;
                                this.user = userData;
                                this.showToast('Google ile giriş başarılı!');
                                document.cookie = 'googleAuthToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                                document.cookie = 'googleAuthUser=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                            } catch (e) { console.error(e); }
                        }
                        window.history.replaceState({}, '', window.location.pathname);
                    }
                    
                    const error = params.get('error');
                    if (error) {
                        const errors = {
                            'oauth_disabled': 'Google ile giriş şu an kullanılamıyor',
                            'google_auth_denied': 'Google girişi reddedildi'
                        };
                        this.showToast(errors[error] || 'Giriş hatası', 'error');
                        window.history.replaceState({}, '', window.location.pathname);
                    }
                },
                
                async logout() {
                    localStorage.removeItem('userToken');
                    localStorage.removeItem('userData');
                    this.isAuthenticated = false;
                    this.user = null;
                    this.showToast('Çıkış yapıldı');
                },
                
                openAuthModal(tab = 'login') {
                    this.authTab = tab;
                    this.authModalOpen = true;
                }
            }
        }
    </script>
    
    @stack('scripts')
</body>
</html>