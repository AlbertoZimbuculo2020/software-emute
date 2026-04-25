<?php

namespace App\Http\Controllers\Configuracoes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SenhaController extends Controller
{
    public function edit()
    {
        return Inertia::render('Configuracoes/Senha');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        DB::table('utilizador')
            ->where('ID_UTILIZADOR', Auth::id())
            ->update([
                'SENHA' => hash('sha512', $validated['password']),
            ]);

        return redirect()->back()->with('message', 'Senha alterada com sucesso!');
    }
}
