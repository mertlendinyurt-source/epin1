@extends('layouts.main')

@section('title', 'Ödeme Başarılı')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-500/20 flex items-center justify-center">
            <i data-lucide="check-circle" class="w-10 h-10 text-green-500"></i>
        </div>
        <h1 class="text-2xl font-bold text-white mb-4">Ödeme Başarılı!</h1>
        <p class="text-zinc-400 mb-8">Siparişiniz başarıyla alındı. UC kodunuz hesabınıza teslim edilecek.</p>
        
        <div class="space-y-3">
            <a href="/account/orders" class="block w-full py-3 btn-primary text-white font-medium rounded-lg text-center">
                Siparişlerime Git
            </a>
            <a href="/" class="block w-full py-3 bg-[#12151a] text-zinc-400 font-medium rounded-lg border border-white/10 hover:text-white transition text-center">
                Ana Sayfaya Dön
            </a>
        </div>
    </div>
</div>
@endsection