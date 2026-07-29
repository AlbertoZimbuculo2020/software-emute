<?php

namespace App\Http\Controllers\Configuracoes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\User;

class UtilizadorController extends Controller
{
    public function index()
    {
        $utilizadores = DB::table('utilizadores_web as u')
            ->leftJoin('tb_perfil as p', 'u.ID_PERFIL', '=', 'p.ID')
            ->select('u.*', 'p.PERFIL as PERFIL_DESC')
            ->get();

        $perfis = DB::table('tb_perfil')->get();

        $medicos = DB::table('tb_tipoentidade')
            ->whereIn('TipoEntidade', ['Medico', 'Medicos'])
            ->where('Estado', 'Ativo')
            ->select('Codigo', 'Nome')
            ->get();

        return Inertia::render('Configuracoes/Utilizadores', [
            'utilizadores' => $utilizadores,
            'perfis' => $perfis,
            'medicos' => $medicos
        ]);
    }

    private function mapEstado(?string $estado): string
    {
        return match($estado) {
            'Inativo', 'Removido' => 'Removido',
            default => 'Activado',
        };
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'NOME_UTILIZADOR' => 'required|string|max:200|unique:utilizador,NOME_UTILIZADOR',
            'SENHA' => 'required|string|min:4',
            'ID_PERFIL' => 'required|integer',
            'ESTADO' => 'nullable|string',
            'ACESSO' => 'nullable|string',
            'ID_PESSOA' => 'nullable|string'
        ]);

        $data['ACESSO'] = $data['ACESSO'] ?? 'NAO';
        $data['ESTADO'] = $this->mapEstado($data['ESTADO'] ?? null);
        $data['CREATED_AT'] = now();

        // Generate token and hash password with it (desktop-compatible)
        $token = \App\Models\User::generateToken();
        $data['REMEMBER_TOKEN'] = $token;
        $data['SENHA'] = \App\Models\User::hashPassword($data['SENHA'], $token);

        // 1. Cria na tabela original e obtem o ID
        $id = DB::table('utilizador')->insertGetId($data);

        // 2. Replica na tabela web com o mesmo ID
        $dataWeb = $data;
        $dataWeb['ID_UTILIZADOR'] = $id;
        DB::table('utilizadores_web')->insert($dataWeb);

        return redirect()->back()->with('message', 'Utilizador criado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'NOME_UTILIZADOR' => 'required|string|max:200|unique:utilizador,NOME_UTILIZADOR,' . $id . ',ID_UTILIZADOR',
            'ID_PERFIL' => 'required|integer',
            'ESTADO' => 'nullable|string',
            'ACESSO' => 'nullable|string',
            'SENHA' => 'nullable|string|min:4',
            'ID_PESSOA' => 'nullable|string'
        ]);

        if (!empty($data['SENHA'])) {
            // Generate new token and hash password with it (desktop-compatible)
            $token = \App\Models\User::generateToken();
            $data['REMEMBER_TOKEN'] = $token;
            $data['SENHA'] = \App\Models\User::hashPassword($data['SENHA'], $token);
        } else {
            unset($data['SENHA']);
        }

        $data['ACESSO'] = $data['ACESSO'] ?? 'NAO';
        $data['ESTADO'] = $this->mapEstado($data['ESTADO'] ?? null);

        // 1. Atualiza tabela original
        DB::table('utilizador')->where('ID_UTILIZADOR', $id)->update($data);

        // 2. Atualiza tabela web (mantem as duas sincronizadas
        $sync = $data;
        $exists = DB::table('utilizadores_web')->where('ID_UTILIZADOR', $id)->exists();
        if ($exists) {
            DB::table('utilizadores_web')->where('ID_UTILIZADOR', $id)->update($data);
        } else {
            // Caso ainda nao exista na web, cria
            $full = DB::table('utilizador')->where('ID_UTILIZADOR', $id)->first();
            if ($full) {
                DB::table('utilizadores_web')->insert((array) $full);
            }
        }

        return redirect()->back()->with('message', 'Utilizador atualizado com sucesso!');
    }

    public function destroy($id)
    {
        // Remove das duas tabelas
        DB::table('utilizador')->where('ID_UTILIZADOR', $id)->delete();
        DB::table('utilizadores_web')->where('ID_UTILIZADOR', $id)->delete();
        return redirect()->back()->with('message', 'Utilizador removido com sucesso!');
    }

    public function perfis()
    {
        $perfis = DB::table('tb_perfil')->get();
        return response()->json($perfis);
    }
}
