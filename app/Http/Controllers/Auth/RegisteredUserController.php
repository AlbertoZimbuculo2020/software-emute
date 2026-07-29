<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $token = \App\Models\User::generateToken();
        $senhaHasheada = User::hashPassword($request->password, $token);
        $agora = now();

        // 1. Cria na tabela original (desktop)
        $id = DB::table('utilizador')->insertGetId([
            'NOME_UTILIZADOR' => $request->name,
            'SENHA' => $senhaHasheada,
            'REMEMBER_TOKEN' => $token,
            'ESTADO' => 'Activado',
            'ACESSO' => 'SIM',
            'CREATED_AT' => $agora,
        ]);

        // 2. Replica na tabela web
        DB::table('utilizadores_web')->insert([
            'ID_UTILIZADOR'   => $id,
            'NOME_UTILIZADOR' => $request->name,
            'SENHA'           => $senhaHasheada,
            'REMEMBER_TOKEN'  => $token,
            'ESTADO'          => 'Activado',
            'ACESSO'          => 'SIM',
            'CREATED_AT'      => $agora,
        ]);

        $user = \App\Models\UtilizadorWeb::find($id);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
