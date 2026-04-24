<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SeguradoraController extends Controller
{
    public function index()
    {
        $seguradoras = DB::table('tb_seguradora')
            ->join('tb_tipoentidade', 'tb_seguradora.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->join('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->select('tb_seguradora.*', 'tb_tipoentidade.Nome', 'tb_tipoentidade.Telefone', 'tb_tipoentidade.Cidade', 'tb_tipoentidade.Codigo', 'tb_entidade.Contribuente')
            ->where('tb_seguradora.Estado', 'Ativo')
            ->get();

        // Calcular próximo ID
        $lastSeg = DB::table('tb_seguradora')
            ->join('tb_tipoentidade', 'tb_seguradora.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->orderBy('tb_seguradora.Id', 'desc')
            ->first();
        
        $nextId = 'SG001';
        if ($lastSeg && preg_match('/SG(\d+)/', $lastSeg->IdTipoEntidade, $matches)) {
            $nextId = 'SG' . str_pad(intval($matches[1]) + 1, 3, '0', STR_PAD_LEFT);
        }

        return Inertia::render('Hospitalar/Seguradoras', [
            'seguradoras' => $seguradoras,
            'nextId' => $nextId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Codigo' => 'required|unique:tb_tipoentidade,Codigo',
            'Nome' => 'required|string|max:250',
            'Contribuinte' => 'required|string|max:20',
            'Telefone' => 'nullable|string|max:20',
            'Cidade' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            DB::table('tb_entidade')->insert([
                'Codigo' => $request->Codigo,
                'Contribuente' => $request->Contribuinte,
                'Tipo' => 'COLECTIVO',
            ]);

            DB::table('tb_tipoentidade')->insert([
                'Codigo' => $request->Codigo,
                'IdEntidade' => $request->Codigo,
                'Nome' => strtoupper($request->Nome),
                'Telefone' => $request->Telefone,
                'Email' => '',
                'Cidade' => $request->Cidade ?? '',
                'Rua' => '',
                'Pais' => 'Angola',
                'TipoEntidade' => 'Seguradora',
                'Estado' => 'Ativo'
            ]);

            DB::table('tb_seguradora')->insert([
                'IdTipoEntidade' => $request->Codigo,
                'Estado' => 'Ativo'
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'Seguradora cadastrada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha ao cadastrar: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Nome' => 'required|string|max:250',
            'Contribuinte' => 'required|string|max:20',
            'Telefone' => 'nullable|string|max:20',
            'Cidade' => 'nullable|string|max:100',
        ]);

        $seguradora = DB::table('tb_seguradora')->where('Id', $id)->first();
        if (!$seguradora) return redirect()->back()->withErrors(['error' => 'Não encontrada']);

        DB::beginTransaction();
        try {
            DB::table('tb_tipoentidade')
                ->where('Codigo', $seguradora->IdTipoEntidade)
                ->update([
                    'Nome' => strtoupper($request->Nome),
                    'Telefone' => $request->Telefone,
                    'Cidade' => $request->Cidade
                ]);

            DB::table('tb_entidade')
                ->where('Codigo', $seguradora->IdTipoEntidade)
                ->update([
                    'Contribuente' => $request->Contribuinte
                ]);

            DB::commit();
            return redirect()->back()->with('message', 'Dados atualizados com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha ao atualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        DB::table('tb_seguradora')->where('Id', $id)->update(['Estado' => 'Removido']);
        return redirect()->back()->with('message', 'Seguradora removida com sucesso!');
    }
}
