<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/advanced', function () {
    return view('advanced');
})->name('advanced');

// API Routes - Public access for AJAX calls
Route::prefix('api/metrics')->group(function () {
    Route::get('trend', [MetricsController::class, 'trend']);
    Route::get('heatmap', [MetricsController::class, 'heatmap']);
    Route::get('service', [MetricsController::class, 'service']);
    Route::get('demand', [MetricsController::class, 'demand']);
    Route::get('stats', [MetricsController::class, 'stats']);
    Route::get('charts', [MetricsController::class, 'charts']);
    Route::get('executive', [MetricsController::class, 'executive']);
    Route::get('advanced', [MetricsController::class, 'advanced']);
    Route::get('predictions', [MetricsController::class, 'predictions']);
    Route::get('leaderboard', [MetricsController::class, 'leaderboard']);
    Route::post('refresh', [MetricsController::class, 'refresh']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
