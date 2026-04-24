<?php

namespace App\Http\Controllers\Entidades;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PacienteController extends Controller
{
    public function index()
    {
        $pacientes = DB::table('tb_paciente')
            ->join('tb_tipoentidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->join('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->where('tb_tipoentidade.TipoEntidade', 'Paciente')
            ->where('tb_paciente.Estado', '!=', 'Removido')
            ->select(
                'tb_paciente.Id',
                'tb_paciente.IdTipoEntidade as Codigo',
                'tb_tipoentidade.Nome',
                'tb_entidade.Contribuente as NIF',
                'tb_entidade.DataNascimento',
                'tb_tipoentidade.Telefone',
                'tb_tipoentidade.Cidade',
                'tb_tipoentidade.Rua',
                'tb_paciente.NSeguradora as Seguradora',
                'tb_entidade.Genero',
                'tb_paciente.Pai',
                'tb_paciente.Mae',
                'tb_tipoentidade.Email'
            )
            ->orderBy('tb_tipoentidade.Nome', 'asc')
            ->get();

        return Inertia::render('Entidades/Pacientes', [
            'pacientes' => $pacientes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'data_nascimento' => 'nullable|date',
            'genero' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:20',
            'rua' => 'nullable|string|max:20',
            'seguradora' => 'nullable|string|max:200',
            'pai' => 'nullable|string|max:150',
            'mae' => 'nullable|string|max:150',
        ]);

        $lastPaciente = DB::table('tb_paciente')
            ->join('tb_tipoentidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->where('tb_tipoentidade.TipoEntidade', 'Paciente')
            ->orderBy('tb_paciente.Id', 'desc')
            ->first();

        $newIdNumber = 1;
        if ($lastPaciente && preg_match('/PC(\d+)/', $lastPaciente->Codigo, $matches)) {
            $newIdNumber = intval($matches[1]) + 1;
        }
        
        $newCodigo = 'PC' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

        DB::table('tb_entidade')->insert([
            'Codigo' => $newCodigo,
            'Contribuente' => $request->nif ?: '999999999',
            'Tipo' => 'SINGULAR',
            'DataNascimento' => $request->data_nascimento,
            'Genero' => $request->genero,
        ]);

        DB::table('tb_tipoentidade')->insert([
            'Codigo' => $newCodigo,
            'IdEntidade' => $newCodigo,
            'Nome' => strtoupper($request->nome),
            'Telefone' => $request->telefone ? substr($request->telefone, 0, 20) : null,
            'Email' => $request->email ? substr($request->email, 0, 20) : null,
            'TipoEntidade' => 'Paciente',
            'Pais' => 'Angola',
            'Cidade' => $request->cidade ? substr($request->cidade, 0, 20) : null,
            'Rua' => $request->rua ? substr($request->rua, 0, 20) : null,
            'Estado' => 'Ativo'
        ]);

        DB::table('tb_paciente')->insert([
            'IdTipoEntidade' => $newCodigo,
            'NSeguradora' => $request->seguradora,
            'Pai' => $request->pai,
            'Mae' => $request->mae,
            'Estado' => 'Ativo'
        ]);

        return redirect()->route('pacientes.index');
    }

    public function update(Request $request, $codigo)
    {
        $request->validate([
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'data_nascimento' => 'nullable|date',
            'genero' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:20',
            'rua' => 'nullable|string|max:20',
            'seguradora' => 'nullable|string|max:200',
            'pai' => 'nullable|string|max:150',
            'mae' => 'nullable|string|max:150',
        ]);

        DB::table('tb_entidade')
            ->where('Codigo', $codigo)
            ->update([
                'Contribuente' => $request->nif ?: '999999999',
                'DataNascimento' => $request->data_nascimento,
                'Genero' => $request->genero,
            ]);

        DB::table('tb_tipoentidade')
            ->where('Codigo', $codigo)
            ->where('TipoEntidade', 'Paciente')
            ->update([
                'Nome' => strtoupper($request->nome),
                'Telefone' => $request->telefone ? substr($request->telefone, 0, 20) : null,
                'Email' => $request->email ? substr($request->email, 0, 20) : null,
                'Cidade' => $request->cidade ? substr($request->cidade, 0, 20) : null,
                'Rua' => $request->rua ? substr($request->rua, 0, 20) : null,
            ]);

        DB::table('tb_paciente')
            ->where('IdTipoEntidade', $codigo)
            ->update([
                'NSeguradora' => $request->seguradora,
                'Pai' => $request->pai,
                'Mae' => $request->mae,
            ]);

        return redirect()->route('pacientes.index');
    }

    public function destroy($codigo)
    {
        DB::table('tb_paciente')
            ->where('IdTipoEntidade', $codigo)
            ->update(['Estado' => 'Removido']);

        return redirect()->route('pacientes.index');
    }
}
