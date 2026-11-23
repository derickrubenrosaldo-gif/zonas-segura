<?php

use Illuminate\Support\Facades\Route;

Route::get('/', static function () {
    return response(file_get_contents(resource_path('views/home.blade.php')))
        ->header('Content-Type', 'text/html; charset=UTF-8');
})->name('home');

Route::prefix('admin')->group(function () {
    Route::get('/acceso', [\App\Http\Controllers\PanelController::class, 'loginForm'])->name('panel.login');
    Route::post('/acceso', [\App\Http\Controllers\PanelController::class, 'login']);
    Route::middleware(\App\Http\Middleware\PanelAccess::class)->group(function () {
        Route::get('/', fn () => redirect()->route('panel.section', 'reportes'));
        Route::post('/salir', [\App\Http\Controllers\PanelController::class, 'logout'])->name('panel.logout');
        Route::get('/{section}/{item?}', [\App\Http\Controllers\PanelController::class, 'section'])
            ->whereIn('section', ['reportes', 'estaciones', 'avisos', 'portada'])->whereNumber('item')->name('panel.section');
        Route::post('/{section}/{item?}', [\App\Http\Controllers\PanelController::class, 'save'])
            ->whereIn('section', ['reportes', 'estaciones', 'avisos', 'portada'])->whereNumber('item')->name('panel.save');
    });
});
