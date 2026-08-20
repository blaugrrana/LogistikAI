<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\WarehouseSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Manajemen barang
    Route::post('/gudang/chat', [WarehouseSetupController::class, 'chat'])->name('warehouses.chat');
    Route::delete('/gudang', [WarehouseSetupController::class, 'destroy'])->name('warehouses.destroy');
    Route::post('/barang', [ItemController::class, 'store'])->name('items.store');
    Route::delete('/barang/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    Route::post('/barang/spotting', [ItemController::class, 'spot'])->name('items.spot');

    // Pengaturan akun
    Route::put('/akun/profil', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::put('/akun/kata-sandi', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::delete('/akun', [AccountController::class, 'destroy'])->name('account.destroy');
});
