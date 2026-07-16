<?php

namespace App\Http\Controllers\Entidades;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RegistoController extends Controller
{
    public function index()
    {
        $consultas = DB::table('tb_consulta')
            ->where('Estado', 'Ativo')
            ->get();

        $seguradoras = DB::table('tb_seguradora')
            ->where('Estado', 'Ativo')
            ->get();

        return Inertia::render('Entidades/Registo', [
            'consultas' => $consultas,
            'seguradoras' => $seguradoras,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:cliente,paciente,medico',
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'rua' => 'nullable|string|max:100',
            'natureza' => 'nullable|in:SINGULAR,COLECTIVO',
            'data_nascimento' => 'nullable|date',
            'genero' => 'nullable|in:Masculino,Feminino',
            'seguradora' => 'nullable|string|max:200',
            'pai' => 'nullable|string|max:150',
            'mae' => 'nullable|string|max:150',
            'carteira_medica' => 'nullable|string|max:150',
        ]);

        DB::beginTransaction();
        try {
            $tipo = $request->tipo;
            $newCodigo = $this->generarCodigo($tipo);

            DB::table('tb_entidade')->insert([
                'Codigo' => $newCodigo,
                'Contribuente' => $request->nif ?: '999999999',
                'Tipo' => $tipo === 'cliente' ? ($request->natureza ?: 'SINGULAR') : 'SINGULAR',
                'DataNascimento' => $tipo === 'paciente' ? $request->data_nascimento : null,
                'Genero' => $tipo === 'paciente' ? $request->genero : null,
            ]);

            DB::table('tb_tipoentidade')->insert([
                'Codigo' => $newCodigo,
                'IdEntidade' => $newCodigo,
                'Nome' => strtoupper($request->nome),
                'Telefone' => $request->telefone ? substr($request->telefone, 0, 20) : ($tipo === 'medico' ? '---' : null),
                'Email' => $request->email ? substr($request->email, 0, 20) : null,
                'TipoEntidade' => $this->getTipoEntidade($tipo),
                'Pais' => 'Angola',
                'Cidade' => $request->cidade ? substr($request->cidade, 0, 20) : ($tipo === 'medico' ? 'S/N' : null),
                'Rua' => $request->rua ? substr($request->rua, 0, 20) : ($tipo === 'medico' ? 'S/N' : null),
                'Estado' => 'Ativo',
            ]);

            if ($tipo === 'paciente') {
                DB::table('tb_paciente')->insert([
                    'IdTipoEntidade' => $newCodigo,
                    'NSeguradora' => $request->seguradora,
                    'Pai' => $request->pai,
                    'Mae' => $request->mae,
                    'Estado' => 'Ativo',
                ]);
            } elseif ($tipo === 'medico') {
                DB::table('tb_medico')->insert([
                    'IdTipoEntidade' => $newCodigo,
                    'CarteiraMedica' => $request->carteira_medica,
                    'Estado' => 'Ativo',
                ]);
            }

            DB::commit();

            $nomeTipo = $tipo === 'cliente' ? 'Cliente' : ($tipo === 'paciente' ? 'Paciente' : 'Médico');
            return redirect()->back()->with('success', "{$nomeTipo} registado com sucesso! Código: {$newCodigo}");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha ao registar: ' . $e->getMessage()]);
        }
    }

    private function generarCodigo($tipo)
    {
        $prefixo = match ($tipo) {
            'cliente' => 'CLI',
            'paciente' => 'PC',
            'medico' => 'MED',
        };

        $last = DB::table('tb_tipoentidade')
            ->where('Codigo', 'like', $prefixo . '%')
            ->orderBy('Id', 'desc')
            ->first();

        $newIdNumber = 1;
        if ($last && preg_match('/' . preg_quote($prefixo) . '(\d+)/', $last->Codigo, $matches)) {
            $newIdNumber = intval($matches[1]) + 1;
        }

        return $prefixo . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);
    }

    private function getTipoEntidade($tipo)
    {
        return match ($tipo) {
            'cliente' => 'Clientes',
            'paciente' => 'Paciente',
            'medico' => 'Medicos',
        };
    }
}
