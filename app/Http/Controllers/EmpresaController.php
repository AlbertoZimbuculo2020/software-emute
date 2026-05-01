<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function create()
    {
        try {
            $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return Inertia::render('Error/DatabaseConnection', [
                'message' => 'A conexão não foi feita ao banco de dados.'
            ]);
        }

        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        return Inertia::render('Empresa/Register', [
            'empresa' => $empresa
        ]);
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
            'IMAGEM' => 'nullable|image|max:4096',
            'LOGIN' => 'nullable|string|max:200',
            'SENHA' => 'nullable|string|min:4'
        ]);

        $empresaData = collect($data)->except(['IMAGEM', 'LOGIN', 'SENHA'])->toArray();
        $empresaData['ESTADO'] = 'Activado';
        $empresaData['CREATED_AT'] = now();

        if ($request->hasFile('IMAGEM')) {
            $empresaData['IMAGEM'] = file_get_contents($request->file('IMAGEM')->getRealPath());
        }

        // Setup the initial admin user if credentials provided
        $userId = 1;
        if (!empty($data['LOGIN']) && !empty($data['SENHA'])) {
            $userExists = DB::table('utilizador')->where('NOME_UTILIZADOR', $data['LOGIN'])->first();
            if (!$userExists) {
                // Determine ID manually if the table doesn't AI correctly, but usually insertGetId assumes AI on primary key
                $userId = DB::table('utilizador')->insertGetId([
                    'NOME_UTILIZADOR' => $data['LOGIN'],
                    'SENHA' => hash('sha512', $data['SENHA']),
                    'ESTADO' => 'Activado',
                    'ACESSO' => 'SIM',
                    'ID_PERFIL' => 1
                ]);
            } else {
                DB::table('utilizador')->where('ID_UTILIZADOR', $userExists->ID_UTILIZADOR)->update([
                    'SENHA' => hash('sha512', (string)$data['SENHA'])
                ]);
                $userId = $userExists->ID_UTILIZADOR;
            }
        } else {
            $firstUser = DB::table('utilizador')->first();
            if ($firstUser) {
                $userId = $firstUser->ID_UTILIZADOR;
            }
        }

        $empresaData['ID_UTILIZADOR'] = $userId;

        $empresaExists = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->exists();
        if ($empresaExists) {
            DB::table('tb_empresa')->where('ID_EMPRESA', 1)->update($empresaData);
        } else {
            $empresaData['ID_EMPRESA'] = 1;
            DB::table('tb_empresa')->insert($empresaData);
        }

        return redirect()->route('login')->with('success', 'Configurações gravadas com sucesso!');
    }
}
