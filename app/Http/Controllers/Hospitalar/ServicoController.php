<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ServicoController extends Controller
{
    public function index()
    {
        $servicos = DB::table('tb_artigo')
            ->where('TIPO', 'SERVICO')
            ->where('ESTADO', 'Activado')
            ->get();

        $subCategorias = DB::table('tb_sub_categoria')->select('CODIGO', 'DESCRICAO')->get();
        $impostos = DB::table('tb_imposto')->select('CODIGO', 'DESCRICAO', 'TAXA')->get();
        $motivosIsencao = DB::table('tb_motivo_isencao')->get();

        // Gerar próximo ID (baseado no padrão da imagem PT000000000)
        $lastArt = DB::table('tb_artigo')
            ->orderBy('ID_ARTIGO', 'desc')
            ->first();
        
        $nextId = 'S0';
        if ($lastArt && preg_match('/S(\d+)/', $lastArt->CODIGO, $matches)) {
            $nextId = 'S' . (intval($matches[1]) + 1);
        }

        return Inertia::render('Hospitalar/Servicos', [
            'servicos' => $servicos,
            'subCategorias' => $subCategorias,
            'impostos' => $impostos,
            'motivosIsencao' => $motivosIsencao,
            'nextId' => $nextId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'CODIGO' => 'required|unique:tb_artigo,CODIGO',
            'DESCRICAO' => 'required|string|max:200',
            'PV' => 'required|numeric',
            'ID_SUBCATEGORIA' => 'nullable',
            'ID_IMPOSTO' => 'nullable',
        ]);

        DB::table('tb_artigo')->insert([
            'CODIGO' => $request->CODIGO,
            'DESCRICAO' => strtoupper($request->DESCRICAO),
            'PV' => $request->PV,
            'PC' => $request->PC ?? 0,
            'CUSTO' => $request->PC ?? 0,
            'MARGEM' => 0,
            'ID_SUBCATEGORIA' => $request->ID_SUBCATEGORIA ?? 'SC000000000',
            'ID_MARCA' => 'MC000000000',
            'ID_UNIDADE' => 'UN000000000',
            'ID_IMPOSTO' => $request->ID_IMPOSTO ?? 'IP000000000',
            'IMAGEM' => $request->IMAGEM,
            'TIPO' => 'SERVICO',
            'ESTADO' => 'Activado',
            'ID_UTILIZADOR' => Auth::id() ?? 1,
            'CREATED_AT' => now()
        ]);

        return redirect()->back()->with('message', 'Serviço cadastrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'DESCRICAO' => 'required|string|max:200',
            'PV' => 'required|numeric',
        ]);

        DB::table('tb_artigo')
            ->where('ID_ARTIGO', $id)
            ->update([
                'DESCRICAO' => strtoupper($request->DESCRICAO),
                'PV' => $request->PV,
                'PC' => $request->PC ?? 0,
                'CUSTO' => $request->PC ?? 0,
                'ID_SUBCATEGORIA' => $request->ID_SUBCATEGORIA ?? 'SC000000000',
                'ID_IMPOSTO' => $request->ID_IMPOSTO ?? 'IP000000000',
                'IMAGEM' => $request->IMAGEM,
            ]);

        return redirect()->back()->with('message', 'Serviço atualizado com sucesso!');
    }

    public function destroy($id)
    {
        DB::table('tb_artigo')->where('ID_ARTIGO', $id)->update(['ESTADO' => 'Removido']);
        return redirect()->back()->with('message', 'Serviço removido com sucesso!');
    }
}
