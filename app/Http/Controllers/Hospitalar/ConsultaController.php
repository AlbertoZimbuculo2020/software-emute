<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ConsultaController extends Controller
{
    public function index()
    {
        $consultas = DB::table('tb_consulta')
            ->where('Estado', '!=', 'Removido')
            ->orderBy('Descricao', 'asc')
            ->get();

        return Inertia::render('Hospitalar/Consultas', [
            'consultas' => $consultas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'descricao' => 'required|string|max:200',
            'valor' => 'required|numeric|min:0',
        ]);

        $lastConsulta = DB::table('tb_consulta')->orderBy('Id', 'desc')->first();
        $newIdNumber = 1;
        if ($lastConsulta && preg_match('/CNT(\d+)/', $lastConsulta->Codigo, $matches)) {
            $newIdNumber = intval($matches[1]) + 1;
        }
        $newCodigo = 'CNT' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

        DB::table('tb_consulta')->insert([
            'Codigo' => $newCodigo,
            'Descricao' => strtoupper($request->descricao),
            'Valor' => $request->valor,
            'Estado' => 'Ativo'
        ]);

        return redirect()->back()->with('message', 'Tipo de consulta cadastrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'descricao' => 'required|string|max:200',
            'valor' => 'required|numeric|min:0',
        ]);

        DB::table('tb_consulta')
            ->where('Id', $id)
            ->update([
                'Descricao' => strtoupper($request->descricao),
                'Valor' => $request->valor,
            ]);

        return redirect()->back()->with('message', 'Consulta atualizada!');
    }

    public function destroy($id)
    {
        DB::table('tb_consulta')
            ->where('Id', $id)
            ->update(['Estado' => 'Removido']);

        return redirect()->back()->with('message', 'Consulta removida!');
    }
}
