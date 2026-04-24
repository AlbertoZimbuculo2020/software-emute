<?php

namespace App\Http\Controllers\Configuracoes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class EmpresaSettingsController extends Controller
{
    public function index()
    {
        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        return Inertia::render('Configuracoes/Empresa', [
            'empresa' => $empresa
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'DESCRICAO' => 'required|string|max:100',
            'NIF' => 'required|string|max:100',
            'NUMEROCOMERCIAL' => 'nullable|string|max:100',
            'REGIME' => 'nullable|string|max:200',
            'TELEFONE' => 'nullable|string|max:200',
            'TELEFONE2' => 'nullable|string|max:200',
            'EMAIL' => 'nullable|email|max:200',
            'PROVINCIA' => 'nullable|string|max:200',
            'CIDADE' => 'nullable|string|max:200',
            'RUA' => 'nullable|string|max:200',
            'INDICATIVO' => 'nullable|string|max:10',
            'IMAGEM' => 'nullable'
        ]);

        $empresaData = collect($data)->except(['IMAGEM'])->toArray();
        
        if ($request->hasFile('IMAGEM')) {
            $empresaData['IMAGEM'] = file_get_contents($request->file('IMAGEM')->getRealPath());
        } elseif ($request->IMAGEM && str_starts_with($request->IMAGEM, 'data:image')) {
            // Keep existing image if not changed
        }

        DB::table('tb_empresa')->where('ID_EMPRESA', 1)->update($empresaData);

        return redirect()->back()->with('message', 'Dados da empresa atualizados com sucesso!');
    }
}
