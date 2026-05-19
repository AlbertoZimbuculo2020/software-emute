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
use App\Http\Controllers\Hospitalar\ServicoController;
use App\Http\Controllers\Hospitalar\EnfermariaController;
use App\Http\Controllers\Hospitalar\InternamentoController;
use App\Http\Controllers\Hospitalar\LaboratorioController;
use App\Http\Controllers\Hospitalar\RaioXController;
use App\Http\Controllers\Configuracoes\EmpresaSettingsController;
use App\Http\Controllers\Configuracoes\SenhaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

    // Top 10 Consultas
    $topConsultas = DB::table('tb_agendamento')
        ->select('Consulta as label', DB::raw('count(*) as count'))
        ->whereNotNull('Consulta')
        ->whereBetween('DataAgendamento', [$startDate, $endDate])
        ->groupBy('Consulta')
        ->orderBy('count', 'desc')
        ->take(10)
        ->get();

    // Top 10 Exames
    $topExames = DB::table('tb_resultado_exame')
        ->select('Descricao as label', DB::raw('count(*) as count'))
        ->whereNotNull('Descricao')
        ->whereBetween('DataExame', [$startDate, $endDate])
        ->groupBy('Descricao')
        ->orderBy('count', 'desc')
        ->take(10)
        ->get();

    // Distribuição por Estado/Situação
    $statusStats = DB::table('tb_agendamento')
        ->select('Situacao as label', DB::raw('count(*) as count'))
        ->whereNotNull('Situacao')
        ->whereBetween('DataAgendamento', [$startDate, $endDate])
        ->groupBy('Situacao')
        ->get();

    // Resumo
    $summary = [
        'totalConsultas' => DB::table('tb_agendamento')->whereBetween('DataAgendamento', [$startDate, $endDate])->count(),
        'totalExames' => DB::table('tb_resultado_exame')->whereBetween('DataExame', [$startDate, $endDate])->count(),
        'totalPacientes' => DB::table('tb_paciente')->count(), // Usually all time
    ];

    // Atividade da Semana (últimos 7 dias em relação à end_date, por exemplo, ou só últimos 7 dias globais)
    // Para simplificar, mantemos os últimos 7 dias a partir de hoje
    $activityLabels = [];
    $activityData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $activityLabels[] = ucfirst($date->locale('pt')->minDayName);
        $activityData[] = DB::table('tb_agendamento')->whereDate('DataAgendamento', $date->format('Y-m-d'))->count();
    }

    // --- NOVOS DADOS PARA O CARD DINÂMICO ---
    
    // Lista de Consultas em Andamento
    $emAndamentoLista = DB::table('tb_agendamento')
        ->join('tb_tipoentidade as p', 'tb_agendamento.IdPaciente', '=', 'p.Codigo')
        ->leftJoin('tb_tipoentidade as m', 'tb_agendamento.IdMedico', '=', 'm.Codigo')
        ->select('p.Nome as Paciente', 'tb_agendamento.Situacao', 'm.Nome as Medico')
        ->whereBetween('tb_agendamento.DataAgendamento', [$startDate, $endDate])
        ->whereNotIn('tb_agendamento.Situacao', ['Finalizado', 'Removido', 'Alta', 'Transferido'])
        ->orderBy('tb_agendamento.Id', 'desc')
        ->get();

    $emAndamentoCount = $emAndamentoLista->count();

    // Consultas Realizadas agrupadas por Médico (Sumário)
    $realizadasPorMedico = DB::table('tb_agendamento')
        ->join('tb_tipoentidade', 'tb_agendamento.IdMedico', '=', 'tb_tipoentidade.Codigo')
        ->select('tb_tipoentidade.Nome as MedicoNome', DB::raw('count(*) as count'))
        ->whereBetween('tb_agendamento.DataAgendamento', [$startDate, $endDate])
        ->where('tb_agendamento.Situacao', 'Finalizado')
        ->groupBy('tb_tipoentidade.Nome')
        ->orderBy('count', 'desc')
        ->get();

    // Lista Detalhada de Consultas Realizadas
    $realizadasLista = DB::table('tb_agendamento')
        ->join('tb_tipoentidade as p', 'tb_agendamento.IdPaciente', '=', 'p.Codigo')
        ->leftJoin('tb_tipoentidade as m', 'tb_agendamento.IdMedico', '=', 'm.Codigo')
        ->select(
            'p.Nome as Paciente', 
            'm.Nome as Medico', 
            'tb_agendamento.DataAgendamento', 
            'tb_agendamento.CREATED_AT as Hora', 
            'tb_agendamento.RECOMENDACOES as Resultado'
        )
        ->whereBetween('tb_agendamento.DataAgendamento', [$startDate, $endDate])
        ->where('tb_agendamento.Situacao', 'Finalizado')
        ->orderBy('tb_agendamento.Id', 'desc')
        ->get();

    return Inertia::render('Dashboard', [
        'topConsultas' => $topConsultas,
        'topExames' => $topExames,
        'statusStats' => $statusStats,
        'summary' => $summary,
        'activityLabels' => $activityLabels,
        'activityData' => $activityData,
        'emAndamentoLista' => $emAndamentoLista,
        'emAndamentoCount' => $emAndamentoCount,
        'realizadasPorMedico' => $realizadasPorMedico,
        'realizadasLista' => $realizadasLista,
        'filtros' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]
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
Route::get('/hospitalar/consultorio/waitlist', [ConsultorioController::class, 'getWaitlist'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.waitlist');
Route::get('/hospitalar/consultorio/paciente/{id}', [ConsultorioController::class, 'getPatientData'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.paciente');
Route::post('/hospitalar/consultorio/store', [ConsultorioController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.store');
Route::post('/hospitalar/consultorio/solicitar-exames', [ConsultorioController::class, 'solicitarExames'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.solicitar-exames');
Route::post('/hospitalar/consultorio/receita', [ConsultorioController::class, 'storeReceita'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.receita.store');
Route::post('/hospitalar/consultorio/receita/remover', [ConsultorioController::class, 'destroyReceitaItem'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.receita.destroy');
Route::post('/hospitalar/consultorio/resultado-exame', [ConsultorioController::class, 'gravarResultadoExame'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.resultado');
Route::post('/hospitalar/consultorio/medicina-ocupacional', [ConsultorioController::class, 'storeMedicinaOcupacional'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.medicina-ocupacional.store');
Route::get('/hospitalar/vacinas', [ConsultorioController::class, 'getVacinas'])->middleware(['auth', 'verified'])->name('hospitalar.vacinas');
Route::post('/hospitalar/consultorio/remover-exame', [ConsultorioController::class, 'removerExameSolicitado'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.remover-exame');
Route::post('/hospitalar/consultorio/encaminhar', [ConsultorioController::class, 'encaminhar'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.encaminhar');
Route::get('/hospitalar/consultorio/imprimir-ficha/{id}', [ConsultorioController::class, 'imprimirFicha'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.imprimir.ficha');
Route::get('/hospitalar/consultorio/imprimir-medicina-ocupacional/{id}', [ConsultorioController::class, 'imprimirMedicinaOcupacional'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.imprimir.medicina-ocupacional');
Route::get('/hospitalar/consultorio/imprimir-receita/{id}', [ConsultorioController::class, 'imprimirReceita'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.imprimir.receita');
Route::get('/hospitalar/consultorio/imprimir-requisicao/{id}', [ConsultorioController::class, 'imprimirRequisicao'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.imprimir.requisicao');
Route::get('/hospitalar/consultorio/imprimir-justificativo/{id}', [ConsultorioController::class, 'imprimirJustificativo'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.imprimir.justificativo');
Route::get('/hospitalar/consultorio/imprimir-guia/{id}', [ConsultorioController::class, 'imprimirGuia'])->middleware(['auth', 'verified'])->name('hospitalar.consultorio.imprimir.guia');

Route::get('/hospitalar/seguradoras', [SeguradoraController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras');
Route::post('/hospitalar/seguradoras', [SeguradoraController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras.store');
Route::put('/hospitalar/seguradoras/{id}', [SeguradoraController::class, 'update'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras.update');
Route::delete('/hospitalar/seguradoras/{id}', [SeguradoraController::class, 'destroy'])->middleware(['auth', 'verified'])->name('hospitalar.seguradoras.destroy');

Route::get('/hospitalar/exames', [ExameController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.exames');
Route::post('/hospitalar/exames', [ExameController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.exames.store');
Route::put('/hospitalar/exames/{id}', [ExameController::class, 'update'])->middleware(['auth', 'verified'])->name('hospitalar.exames.update');
Route::delete('/hospitalar/exames/{id}', [ExameController::class, 'destroy'])->middleware(['auth', 'verified'])->name('hospitalar.exames.destroy');

Route::get('/hospitalar/servicos', [ServicoController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.servicos');
Route::post('/hospitalar/servicos', [ServicoController::class, 'store'])->middleware(['auth', 'verified'])->name('hospitalar.servicos.store');
Route::put('/hospitalar/servicos/{id}', [ServicoController::class, 'update'])->middleware(['auth', 'verified'])->name('hospitalar.servicos.update');
Route::delete('/hospitalar/servicos/{id}', [ServicoController::class, 'destroy'])->middleware(['auth', 'verified'])->name('hospitalar.servicos.destroy');

Route::get('/hospitalar/enfermaria', [EnfermariaController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.enfermaria.index');
Route::get('/hospitalar/enfermaria/details/{id}', [EnfermariaController::class, 'getDetails'])->middleware(['auth', 'verified'])->name('hospitalar.enfermaria.details');
Route::post('/hospitalar/enfermaria/resultado', [EnfermariaController::class, 'salvarResultado'])->middleware(['auth', 'verified'])->name('hospitalar.enfermaria.resultado');
Route::post('/hospitalar/enfermaria/farmaco', [EnfermariaController::class, 'storeFarmaco'])->middleware(['auth', 'verified'])->name('hospitalar.enfermaria.farmaco');
Route::post('/hospitalar/enfermaria/finalizar/{id}', [EnfermariaController::class, 'finalizarAtendimento'])->middleware(['auth', 'verified'])->name('hospitalar.enfermaria.finalizar');

Route::get('/hospitalar/internamento', [InternamentoController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.index');
Route::get('/hospitalar/internamento/details/{id}', [InternamentoController::class, 'getDetails'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.details');
Route::post('/hospitalar/internamento/prescricao', [InternamentoController::class, 'storePrescricao'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.prescricao.store');
Route::post('/hospitalar/internamento/prescricao/toggle/{id}', [InternamentoController::class, 'toggleCumprimento'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.prescricao.toggle');
Route::post('/hospitalar/internamento/ato', [InternamentoController::class, 'storeAto'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.ato');
Route::post('/hospitalar/internamento/sinais', [InternamentoController::class, 'storeSinaisVitais'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.sinais.store');
Route::post('/hospitalar/internamento/alta/{id}', [InternamentoController::class, 'darAlta'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.alta');
Route::get('/hospitalar/internamento/imprimir-processo/{id}', [InternamentoController::class, 'imprimirProcesso'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.imprimir.processo');
Route::get('/hospitalar/internamento/imprimir-atos-enfermagem/{id}', [InternamentoController::class, 'imprimirAtosEnfermagem'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.imprimir.atos-enfermagem');
Route::get('/hospitalar/internamento/imprimir-vitais/{id}', [InternamentoController::class, 'imprimirVitais'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.imprimir.vitais');
Route::get('/hospitalar/internamento/depositos', [InternamentoController::class, 'getDepositos'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.depositos');
Route::get('/hospitalar/internamento/artigos', [InternamentoController::class, 'getArtigos'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.artigos');
Route::post('/hospitalar/internamento/cumprimento', [InternamentoController::class, 'gravarCumprimento'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.cumprimento');
Route::get('/hospitalar/internamento/imprimir-cumprimento/{id}', [InternamentoController::class, 'imprimirCumprimento'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.imprimir.cumprimento');
Route::post('/hospitalar/internamento/saida-farmaco', [InternamentoController::class, 'finalizarSaidaFarmaco'])->middleware(['auth', 'verified'])->name('hospitalar.internamento.saida-farmaco');

Route::get('/hospitalar/laboratorio', [LaboratorioController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.index');
Route::get('/hospitalar/laboratorio/details/{id}', [LaboratorioController::class, 'getDetails'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.details');
Route::post('/hospitalar/laboratorio/resultado', [LaboratorioController::class, 'salvarResultado'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.resultado');
Route::post('/hospitalar/laboratorio/material', [LaboratorioController::class, 'storeMaterial'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.material.store');
Route::delete('/hospitalar/laboratorio/material/{id}', [LaboratorioController::class, 'destroyMaterial'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.material.destroy');
Route::post('/hospitalar/laboratorio/finalizar/{id}', [LaboratorioController::class, 'finalizarAtendimento'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.finalizar');
Route::get('/hospitalar/laboratorio/imprimir/{id}', [LaboratorioController::class, 'imprimirPDF'])->middleware(['auth', 'verified'])->name('hospitalar.laboratorio.imprimir');
Route::get('/hospitalar/raiox', [RaioXController::class, 'index'])->middleware(['auth', 'verified'])->name('hospitalar.raiox.index');
Route::get('/hospitalar/raiox/details/{id}', [RaioXController::class, 'getDetails'])->middleware(['auth', 'verified'])->name('hospitalar.raiox.details');
Route::post('/hospitalar/raiox/resultado', [RaioXController::class, 'salvarResultado'])->middleware(['auth', 'verified'])->name('hospitalar.raiox.resultado');
Route::post('/hospitalar/raiox/finalizar/{id}', [RaioXController::class, 'finalizarAtendimento'])->middleware(['auth', 'verified'])->name('hospitalar.raiox.finalizar');

Route::get('/configuracoes/empresa', [EmpresaSettingsController::class, 'index'])->middleware(['auth', 'verified'])->name('configuracoes.empresa.index');
Route::post('/configuracoes/empresa/update', [EmpresaSettingsController::class, 'update'])->middleware(['auth', 'verified'])->name('configuracoes.empresa.update');

Route::get('/configuracoes/senha', [SenhaController::class, 'edit'])->middleware(['auth', 'verified'])->name('configuracoes.senha.index');
Route::post('/configuracoes/senha/update', [SenhaController::class, 'update'])->middleware(['auth', 'verified'])->name('configuracoes.senha.update');

Route::get('/configuracoes/utilizadores', [\App\Http\Controllers\Configuracoes\UtilizadorController::class, 'index'])->middleware(['auth', 'verified'])->name('configuracoes.utilizadores.index');
Route::post('/configuracoes/utilizadores', [\App\Http\Controllers\Configuracoes\UtilizadorController::class, 'store'])->middleware(['auth', 'verified'])->name('configuracoes.utilizadores.store');
Route::put('/configuracoes/utilizadores/{id}', [\App\Http\Controllers\Configuracoes\UtilizadorController::class, 'update'])->middleware(['auth', 'verified'])->name('configuracoes.utilizadores.update');
Route::delete('/configuracoes/utilizadores/{id}', [\App\Http\Controllers\Configuracoes\UtilizadorController::class, 'destroy'])->middleware(['auth', 'verified'])->name('configuracoes.utilizadores.destroy');
Route::get('/configuracoes/utilizadores/perfis', [\App\Http\Controllers\Configuracoes\UtilizadorController::class, 'perfis'])->middleware(['auth', 'verified'])->name('configuracoes.utilizadores.perfis');

Route::get('/configuracoes/permissoes', [\App\Http\Controllers\Configuracoes\PermissaoController::class, 'index'])->middleware(['auth', 'verified'])->name('configuracoes.permissoes.index');
Route::get('/configuracoes/permissoes/{profileId}', [\App\Http\Controllers\Configuracoes\PermissaoController::class, 'getPermissions'])->middleware(['auth', 'verified'])->name('configuracoes.permissoes.get');
Route::post('/configuracoes/permissoes/update', [\App\Http\Controllers\Configuracoes\PermissaoController::class, 'update'])->middleware(['auth', 'verified'])->name('configuracoes.permissoes.update');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/test-database-connection', [AuthenticatedSessionController::class, 'testConnection'])->name('db.test');
Route::post('/save-database-connection', [AuthenticatedSessionController::class, 'saveConnection'])->name('db.save');

// Licença
Route::get('/licenca', [\App\Http\Controllers\LicencaController::class, 'index'])->name('licenca.index');
Route::post('/licenca/solicitar', [\App\Http\Controllers\LicencaController::class, 'solicitar'])->name('licenca.solicitar');
Route::post('/licenca/ativar', [\App\Http\Controllers\LicencaController::class, 'ativar'])->name('licenca.ativar');

require __DIR__.'/auth.php';
