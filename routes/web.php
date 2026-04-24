<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Hospitalar\RecepcaoController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/hospitalar/recepcao', [RecepcaoController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao');
Route::post('/hospitalar/recepcao/search', [RecepcaoController::class, 'searchPaciente'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao.search');
Route::post('/hospitalar/recepcao/store', [RecepcaoController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
