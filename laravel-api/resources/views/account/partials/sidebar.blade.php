<!-- Account Sidebar -->
<div class="w-full md:w-64 shrink-0">
    <div class="bg-[#12151a] rounded-xl border border-white/5 overflow-hidden">
        <div class="p-4 border-b border-white/5">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center">
                    <span class="text-lg font-bold text-white" x-text="user?.firstName?.charAt(0) || 'U'"></span>
                </div>
                <div>
                    <p class="font-medium text-white" x-text="user?.firstName + ' ' + user?.lastName"></p>
                    <p class="text-sm text-zinc-400" x-text="user?.email"></p>
                </div>
            </div>
        </div>
        
        <nav class="p-2">
            <a href="/account" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5 transition {{ request()->is('account') && !request()->is('account/*') ? 'bg-white/5 text-white' : '' }}">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Dashboard</span>
            </a>
            <a href="/account/orders" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5 transition {{ request()->is('account/orders*') ? 'bg-white/5 text-white' : '' }}">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <span>Siparişlerim</span>
            </a>
            <a href="/account/profile" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5 transition {{ request()->is('account/profile') ? 'bg-white/5 text-white' : '' }}">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span>Profil</span>
            </a>
            <a href="/account/security" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5 transition {{ request()->is('account/security') ? 'bg-white/5 text-white' : '' }}">
                <i data-lucide="shield" class="w-5 h-5"></i>
                <span>Güvenlik</span>
            </a>
            <a href="/account/support" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white hover:bg-white/5 transition {{ request()->is('account/support*') ? 'bg-white/5 text-white' : '' }}">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span>Destek</span>
            </a>
        </nav>
        
        <div class="p-2 border-t border-white/5">
            <button @click="logout()" class="flex items-center space-x-3 w-full px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10 transition">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span>Çıkış Yap</span>
            </button>
        </div>
    </div>
</div>