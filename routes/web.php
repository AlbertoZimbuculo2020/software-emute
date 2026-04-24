<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Controllers\Hospitalar\RecepcaoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\Entidades\ClienteController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    // Top 10 Consultas
    $topConsultas = DB::table('tb_agendamento')
        ->select('Consulta as label', DB::raw('count(*) as count'))
        ->whereNotNull('Consulta')
        ->groupBy('Consulta')
        ->orderBy('count', 'desc')
        ->take(10)
        ->get();

    // Top 10 Exames
    $topExames = DB::table('tb_resultado_exame')
        ->select('Descricao as label', DB::raw('count(*) as count'))
        ->whereNotNull('Descricao')
        ->groupBy('Descricao')
        ->orderBy('count', 'desc')
        ->take(10)
        ->get();

    // Distribuição por Estado/Situação
    $statusStats = DB::table('tb_agendamento')
        ->select('Situacao as label', DB::raw('count(*) as count'))
        ->whereNotNull('Situacao')
        ->groupBy('Situacao')
        ->get();

    // Resumo
    $summary = [
        'totalConsultas' => DB::table('tb_agendamento')->whereMonth('DataAgendamento', now()->month)->count(),
        'totalExames' => DB::table('tb_resultado_exame')->whereMonth('DataExame', now()->month)->count(),
        'totalPacientes' => DB::table('tb_paciente')->count(),
    ];

    return Inertia::render('Dashboard', [
        'topConsultas' => $topConsultas,
        'topExames' => $topExames,
        'statusStats' => $statusStats,
        'summary' => $summary
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/hospitalar/recepcao', [RecepcaoController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao');
Route::post('/hospitalar/recepcao/search', [RecepcaoController::class, 'searchPaciente'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao.search');
Route::post('/hospitalar/recepcao/store', [RecepcaoController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao.store');

Route::get('/empresa/register', [EmpresaController::class, 'create'])->name('empresa.register');
Route::post('/empresa/register', [EmpresaController::class, 'store'])->name('empresa.store');

Route::get('/entidades/clientes', [ClienteController::class, 'index'])->middleware(['auth', 'verified'])->name('clientes.index');
Route::post('/entidades/clientes', [ClienteController::class, 'store'])->middleware(['auth', 'verified'])->name('clientes.store');
Route::put('/entidades/clientes/{codigo}', [ClienteController::class, 'update'])->middleware(['auth', 'verified'])->name('clientes.update');
Route::delete('/entidades/clientes/{codigo}', [ClienteController::class, 'destroy'])->middleware(['auth', 'verified'])->name('clientes.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
