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

        $token = \App\Models\User::generateToken();

        DB::table('utilizador')
            ->where('ID_UTILIZADOR', Auth::id())
            ->update([
                'SENHA' => \App\Models\User::hashPassword($validated['password'], $token),
                'REMEMBER_TOKEN' => $token,
            ]);

        return redirect()->back()->with('message', 'Senha alterada com sucesso!');
    }
}
