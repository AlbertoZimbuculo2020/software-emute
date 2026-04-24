<?php

namespace App\Http\Controllers\Entidades;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index()
    {
        // Ler clientes cruzando tb_tipoentidade com tb_entidade
        $clientes = DB::table('tb_tipoentidade')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->where('tb_tipoentidade.TipoEntidade', 'Clientes')
            ->where('tb_tipoentidade.Estado', '!=', 'Removido')
            ->select(
                'tb_tipoentidade.Id',
                'tb_tipoentidade.Codigo',
                'tb_tipoentidade.Nome',
                'tb_tipoentidade.Telefone',
                'tb_tipoentidade.Email',
                'tb_entidade.Contribuente as NIF',
                'tb_tipoentidade.Cidade',
                'tb_entidade.Tipo as Natureza'
            )
            ->orderBy('tb_tipoentidade.Nome', 'asc')
            ->get();

        return Inertia::render('Entidades/Clientes', [
            'clientes' => $clientes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:100', // Relaxed email constraint since DB is varchar(20), maybe user enters larger? Let's keep max:50 just in case. DB says varchar(20) but we should cap at 20.
            'natureza' => 'required|in:SINGULAR,COLECTIVO',
            'cidade' => 'nullable|string|max:20',
            'rua' => 'nullable|string|max:20',
        ]);

        // Generates a new code CLI00X
        $lastClient = DB::table('tb_tipoentidade')
            ->where('TipoEntidade', 'Clientes')
            ->orderBy('Id', 'desc')
            ->first();

        $newIdNumber = 1;
        if ($lastClient && preg_match('/CLI(\d+)/', $lastClient->Codigo, $matches)) {
            $newIdNumber = intval($matches[1]) + 1;
        }
        
        $newCodigo = 'CLI' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

        // Insert into tb_entidade
        DB::table('tb_entidade')->insert([
            'Codigo' => $newCodigo,
            'Contribuente' => $request->nif ?: '999999999',
            'Tipo' => $request->natureza,
        ]);

        // Insert into tb_tipoentidade
        DB::table('tb_tipoentidade')->insert([
            'Codigo' => $newCodigo,
            'IdEntidade' => $newCodigo,
            'Nome' => strtoupper($request->nome),
            'Telefone' => $request->telefone ? substr($request->telefone, 0, 20) : null,
            'Email' => $request->email ? substr($request->email, 0, 20) : null,
            'TipoEntidade' => 'Clientes',
            'Pais' => 'Angola',
            'Cidade' => $request->cidade ? substr($request->cidade, 0, 20) : null,
            'Rua' => $request->rua ? substr($request->rua, 0, 20) : null,
            'Estado' => 'Ativo'
        ]);

        return redirect()->route('clientes.index');
    }

    public function update(Request $request, $codigo)
    {
        $request->validate([
            'nome' => 'required|string|max:250',
            'nif' => 'nullable|string|max:25',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:100',
            'natureza' => 'required|in:SINGULAR,COLECTIVO',
            'cidade' => 'nullable|string|max:20',
            'rua' => 'nullable|string|max:20',
        ]);

        DB::table('tb_entidade')
            ->where('Codigo', $codigo)
            ->update([
                'Contribuente' => $request->nif ?: '999999999',
                'Tipo' => $request->natureza,
            ]);

        DB::table('tb_tipoentidade')
            ->where('Codigo', $codigo)
            ->where('TipoEntidade', 'Clientes')
            ->update([
                'Nome' => strtoupper($request->nome),
                'Telefone' => $request->telefone ? substr($request->telefone, 0, 20) : null,
                'Email' => $request->email ? substr($request->email, 0, 20) : null,
                'Cidade' => $request->cidade ? substr($request->cidade, 0, 20) : null,
                'Rua' => $request->rua ? substr($request->rua, 0, 20) : null,
            ]);

        return redirect()->route('clientes.index');
    }

    public function destroy($codigo)
    {
        DB::table('tb_tipoentidade')
            ->where('Codigo', $codigo)
            ->where('TipoEntidade', 'Clientes')
            ->update(['Estado' => 'Removido']);

        return redirect()->route('clientes.index');
    }
}
