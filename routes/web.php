<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Controllers\Hospitalar\RecepcaoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\Entidades\ClienteController;
use App\Http\Controllers\Entidades\PacienteController;
use App\Http\Controllers\Entidades\MedicoController;
use App\Http\Controllers\Hospitalar\ConsultaController;
use App\Http\Controllers\Hospitalar\TriagemController;
use App\Http\Controllers\Hospitalar\ConsultorioController;
use App\Http\Controllers\Hospitalar\SeguradoraController;
use App\Http\Controllers\Hospitalar\ExameController;

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
Route::post('/hospitalar/recepcao/enviar-triagem', [RecepcaoController::class, 'enviarTriagem'])->middleware(['auth', 'verified'])->name('hospitalar.recepcao.enviar-triagem');

Route::get('/empresa/register', [EmpresaController::class, 'create'])->name('empresa.register');
Route::post('/empresa/register', [EmpresaController::class, 'store'])->name('empresa.store');

Route::get('/entidades/clientes', [ClienteController::class, 'index'])->middleware(['auth', 'verified'])->name('clientes.index');
Route::post('/entidades/clientes', [ClienteController::class, 'store'])->middleware(['auth', 'verified'])->name('clientes.store');
Route::put('/entidades/clientes/{codigo}', [ClienteController::class, 'update'])->middleware(['auth', 'verified'])->name('clientes.update');
Route::delete('/entidades/clientes/{codigo}', [ClienteController::class, 'destroy'])->middleware(['auth', 'verified'])->name('clientes.destroy');

Route::get('/entidades/pacientes', [PacienteController::class, 'index'])->middleware(['auth', 'verified'])->name('pacientes.index');
Route::post('/entidades/pacientes', [PacienteController::class, 'store'])->middleware(['auth', 'verified'])->name('pacientes.store');
Route::put('/entidades/pacientes/{codigo}', [PacienteController::class, 'update'])->middleware(['auth', 'verified'])->name('pacientes.update');
Route::delete('/entidades/pacientes/{codigo}', [PacienteController::class, 'destroy'])->middleware(['auth', 'verified'])->name('pacientes.destroy');

Route::get('/entidades/medicos', [MedicoController::class, 'index'])->middleware(['auth', 'verified'])->name('medicos.index');
Route::post('/entidades/medicos', [MedicoController::class, 'store'])->middleware(['auth', 'verified'])->name('medicos.store');
Route::put('/entidades/medicos/{codigo}', [MedicoController::class, 'update'])->middleware(['auth', 'verified'])->name('medicos.update');
Route::delete('/entidades/medicos/{codigo}', [MedicoController::class, 'destroy'])->middleware(['auth', 'verified'])->name('medicos.destroy');
Route::post('/entidades/medicos/consultas', [MedicoController::class, 'associarConsulta'])->middleware(['auth', 'verified'])->name('medicos.consultas.store');
Route::delete('/entidades/medicos/consultas/{id}', [MedicoController::class, 'removerConsulta'])->middleware(['auth', 'verified'])->name('medicos.consultas.destroy');

Route::get('/hospitalar/consultas', [ConsultaController::class, 'index'])->middleware(['auth', 'verified'])->name('consultas.index');
Route::post('/hospitalar/consultas', [ConsultaController::class, 'store'])->middleware(['auth', 'verified'])->name('consultas.store');
Route::put('/hospitalar/consultas/{id}', [ConsultaController::class, 'update'])->middleware(['auth', 'verified'])->name('consultas.update');
Route::delete('/hospitalar/consultas/{id}', [ConsultaController::class, 'destroy'])->middleware(['auth', 'verified'])->name('consultas.destroy');

Route::get('/hospitalar/triagem', [TriagemController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.triagem');
Route::post('/hospitalar/triagem', [TriagemController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.triagem.store');

Route::get('/hospitalar/consultorio', [ConsultorioController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio');
Route::get('/hospitalar/consultorio/paciente/{id}', [ConsultorioController::class, 'getPatientData'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.paciente');
Route::post('/hospitalar/consultorio/store', [ConsultorioController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.store');

Route::get('/hospitalar/seguradoras', [SeguradoraController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras');
Route::post('/hospitalar/seguradoras', [SeguradoraController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras.store');
Route::put('/hospitalar/seguradoras/{id}', [SeguradoraController::class, 'update'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras.update');
Route::delete('/hospitalar/seguradoras/{id}', [SeguradoraController::class, 'destroy'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras.destroy');

Route::get('/hospitalar/exames', [ExameController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.exames');
Route::post('/hospitalar/exames', [ExameController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.exames.store');
Route::put('/hospitalar/exames/{id}', [ExameController::class, 'update'])->middleware(['auth', 'verified'])->name('hospitalar.exames.update');
Route::delete('/hospitalar/exames/{id}', [ExameController::class, 'destroy'])->middleware(['auth', 'verified'])->name('hospitalar.exames.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
