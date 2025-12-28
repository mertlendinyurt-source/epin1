@extends('layouts.main')

@section('title', 'Ödeme Başarısız')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-red-500/20 flex items-center justify-center">
            <i data-lucide="x-circle" class="w-10 h-10 text-red-500"></i>
        </div>
        <h1 class="text-2xl font-bold text-white mb-4">Ödeme Başarısız</h1>
        <p class="text-zinc-400 mb-8">Ödeme işlemi tamamlanamadı. Lütfen tekrar deneyin veya destek ekibiyle iletişime geçin.</p>
        
        <div class="space-y-3">
            <a href="/" class="block w-full py-3 btn-primary text-white font-medium rounded-lg text-center">
                Tekrar Dene
            </a>
            <a href="/account/support/new" class="block w-full py-3 bg-[#12151a] text-zinc-400 font-medium rounded-lg border border-white/10 hover:text-white transition text-center">
                Destek Talebi Oluştur
            </a>
        </div>
    </div>
</div>
@endsection