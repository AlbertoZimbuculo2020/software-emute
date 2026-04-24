<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function create()
    {
        return Inertia::render('Empresa/Register');
    }

    public function store(Request $request)
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
        ]);

        $data['ESTADO'] = 'Activado';
        $data['CREATED_AT'] = now();

        DB::table('tb_empresa')->insert($data);

        return redirect()->route('login')->with('success', 'Empresa cadastrada com sucesso!');
    }
}
