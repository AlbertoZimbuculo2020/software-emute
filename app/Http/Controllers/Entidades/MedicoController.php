<?php

namespace App\Http\Controllers\Entidades;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MedicoController extends Controller
{
    public function index()
    {
        $medicos = DB::table('tb_medico')
            ->join('tb_tipoentidade', 'tb_medico.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->join('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->where('tb_medico.Estado', 'Ativo')
            ->select(
                'tb_medico.Id',
                'tb_medico.IdTipoEntidade as Codigo',
                'tb_tipoentidade.Nome',
                'tb_entidade.Contribuente as NIF',
                'tb_medico.CarteiraMedica',
                'tb_tipoentidade.Telefone',
                'tb_tipoentidade.Cidade',
                'tb_tipoentidade.Rua'
            )
            ->get();

        $consultas = DB::table('tb_consulta')
            ->where('Estado', 'Ativo')
            ->get();

        // Get associations for all doctors to pass to frontend
        $associacoes = DB::table('tb_consulta_medico')
            ->where('Estado', 'Ativo')
            ->get();

        return Inertia::render('Entidades/Medicos', [
            'medicos' => $medicos,
            'consultas' => $consultas,
            'associacoes' => $associacoes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'carteira_medica' => 'nullable|string|max:150',
            'telefone' => 'nullable|string|max:20',
            'cidade' => 'nullable|string|max:100',
            'rua' => 'nullable|string|max:100',
        ]);

        $lastMedico = DB::table('tb_medico')
            ->join('tb_tipoentidade', 'tb_medico.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->orderBy('tb_medico.Id', 'desc')
            ->first();

        $newIdNumber = 1;
        if ($lastMedico && preg_match('/MED(\d+)/', $lastMedico->IdTipoEntidade, $matches)) {
            $newIdNumber = intval($matches[1]) + 1;
        }
        $newCodigo = 'MED' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            DB::table('tb_entidade')->insert([
                'Codigo' => $newCodigo,
                'Contribuente' => $request->nif ?: '999999999',
                'Tipo' => 'SINGULAR',
            ]);

            DB::table('tb_tipoentidade')->insert([
                'Codigo' => $newCodigo,
                'IdEntidade' => $newCodigo,
                'Nome' => strtoupper($request->nome),
                'Telefone' => $request->telefone,
                'TipoEntidade' => 'Medico',
                'Pais' => 'Angola',
                'Cidade' => $request->cidade,
                'Rua' => $request->rua,
                'Estado' => 'Ativo'
            ]);

            DB::table('tb_medico')->insert([
                'IdTipoEntidade' => $newCodigo,
                'CarteiraMedica' => $request->carteira_medica,
                'Estado' => 'Ativo'
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'Médico cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha ao cadastrar médico: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $codigo)
    {
        $request->validate([
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'carteira_medica' => 'nullable|string|max:150',
            'telefone' => 'nullable|string|max:20',
            'cidade' => 'nullable|string|max:100',
            'rua' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            DB::table('tb_entidade')
                ->where('Codigo', $codigo)
                ->update([
                    'Contribuente' => $request->nif ?: '999999999',
                ]);

            DB::table('tb_tipoentidade')
                ->where('Codigo', $codigo)
                ->update([
                    'Nome' => strtoupper($request->nome),
                    'Telefone' => $request->telefone,
                    'Cidade' => $request->cidade,
                    'Rua' => $request->rua,
                ]);

            DB::table('tb_medico')
                ->where('IdTipoEntidade', $codigo)
                ->update([
                    'CarteiraMedica' => $request->carteira_medica,
                ]);

            DB::commit();
            return redirect()->back()->with('message', 'Dados do médico atualizados!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha ao atualizar médico: ' . $e->getMessage()]);
        }
    }

    public function destroy($codigo)
    {
        DB::table('tb_medico')
            ->where('IdTipoEntidade', $codigo)
            ->update(['Estado' => 'Removido']);

        return redirect()->back()->with('message', 'Médico removido com sucesso!');
    }

    public function associarConsulta(Request $request)
    {
        $request->validate([
            'IdTipoEntidade' => 'required',
            'IdConsulta' => 'required',
        ]);

        $consulta = DB::table('tb_consulta')->where('Id', $request->IdConsulta)->first();
        
        DB::table('tb_consulta_medico')->insert([
            'IdTipoEntidade' => $request->IdTipoEntidade,
            'IdConsulta' => $request->IdConsulta,
            'Descricao' => $consulta ? $consulta->Descricao : '',
            'Estado' => 'Ativo'
        ]);

        return redirect()->back()->with('message', 'Consulta associada com sucesso!');
    }

    public function removerConsulta($id)
    {
        DB::table('tb_consulta_medico')
            ->where('Id', $id)
            ->update(['Estado' => 'Removido']);

        return redirect()->back()->with('message', 'Associação removida!');
    }
}
