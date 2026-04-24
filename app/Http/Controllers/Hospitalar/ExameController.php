<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExameController extends Controller
{
    public function index()
    {
        $exames = DB::table('tb_exames')
            ->where('Estado', 'Ativo')
            ->get();

        // Calcular próximo ID
        $lastEx = DB::table('tb_exames')
            ->orderBy('Id', 'desc')
            ->first();
        
        $nextId = 'EXM1';
        if ($lastEx && preg_match('/EXM(\d+)/', $lastEx->Codigo, $matches)) {
            $nextId = 'EXM' . (intval($matches[1]) + 1);
        }

        return Inertia::render('Hospitalar/Exames', [
            'exames' => $exames,
            'nextId' => $nextId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Codigo' => 'required|unique:tb_exames,Codigo',
            'Descricao' => 'required|string|max:200',
            'Valor' => 'required|numeric',
            'Categoria' => 'nullable|string',
            'Tipo' => 'nullable|string',
            'Exame_Fora' => 'required|string',
        ]);

        DB::table('tb_exames')->insert([
            'Codigo' => $request->Codigo,
            'Descricao' => strtoupper($request->Descricao),
            'Valor' => $request->Valor,
            'Categoria' => $request->Categoria,
            'Tipo' => $request->Tipo ?? 'NORMAL',
            'Filhos' => $request->Filhos ? json_encode($request->Filhos) : null,
            'Referencia' => $request->Referencia,
            'Sugestao' => $request->Sugestao,
            'Exame_Fora' => $request->Exame_Fora,
            'USER' => Auth::user()->name,
            'Estado' => 'Ativo'
        ]);

        return redirect()->back()->with('message', 'Exame cadastrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Descricao' => 'required|string|max:200',
            'Valor' => 'required|numeric',
            'Categoria' => 'nullable|string',
            'Tipo' => 'nullable|string',
            'Exame_Fora' => 'required|string',
        ]);

        DB::table('tb_exames')
            ->where('Id', $id)
            ->update([
                'Descricao' => strtoupper($request->Descricao),
                'Valor' => $request->Valor,
                'Categoria' => $request->Categoria,
                'Tipo' => $request->Tipo,
                'Filhos' => $request->Filhos ? json_encode($request->Filhos) : null,
                'Referencia' => $request->Referencia,
                'Sugestao' => $request->Sugestao,
                'Exame_Fora' => $request->Exame_Fora,
            ]);

        return redirect()->back()->with('message', 'Exame atualizado com sucesso!');
    }

    public function destroy($id)
    {
        DB::table('tb_exames')->where('Id', $id)->update(['Estado' => 'Removido']);
        return redirect()->back()->with('message', 'Exame removido com sucesso!');
    }
}
