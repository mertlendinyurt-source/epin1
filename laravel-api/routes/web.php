<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| React SPA için catch-all route
*/

// React SPA - Tüm route'ları index.html'e yönlendir
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');