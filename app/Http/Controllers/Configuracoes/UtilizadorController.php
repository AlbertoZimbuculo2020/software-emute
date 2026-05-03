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
        $utilizadores = DB::table('utilizador as u')
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

        $data['SENHA'] = hash('sha512', $data['SENHA']);
        $data['ACESSO'] = $data['ACESSO'] ?? 'NAO';
        $data['ESTADO'] = $this->mapEstado($data['ESTADO'] ?? null);
        $data['CREATED_AT'] = now();

        DB::table('utilizador')->insert($data);

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
            $data['SENHA'] = hash('sha512', $data['SENHA']);
        } else {
            unset($data['SENHA']);
        }

        $data['ACESSO'] = $data['ACESSO'] ?? 'NAO';
        $data['ESTADO'] = $this->mapEstado($data['ESTADO'] ?? null);

        DB::table('utilizador')->where('ID_UTILIZADOR', $id)->update($data);

        return redirect()->back()->with('message', 'Utilizador atualizado com sucesso!');
    }

    public function destroy($id)
    {
        DB::table('utilizador')->where('ID_UTILIZADOR', $id)->delete();
        return redirect()->back()->with('message', 'Utilizador removido com sucesso!');
    }

    public function perfis()
    {
        $perfis = DB::table('tb_perfil')->get();
        return response()->json($perfis);
    }
}
