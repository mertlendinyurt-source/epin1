@extends('layouts.main')

@section('title', $page['title'])
@section('page_title', $page['title'])

@section('content')
<div>
    <!-- Header -->
    @include('partials.header')
    
    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold text-white mb-8">{{ $page['title'] }}</h1>
        
        <div class="bg-[#12151a] rounded-xl border border-white/5 p-6 md:p-8">
            <div class="prose prose-invert max-w-none">
                {!! nl2br(e($page['content'])) !!}
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <a href="/" class="text-blue-400 hover:text-blue-300 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
                Ana Sayfaya Dön
            </a>
        </div>
    </div>
</div>
@endsection