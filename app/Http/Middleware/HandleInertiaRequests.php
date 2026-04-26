<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = [];

        if ($user) {
            // Se for Admin, tem acesso a tudo
            if ($user->ACESSO === 'SIM') {
                $permissions = ['*'];
            } else {
                $permissions = \Illuminate\Support\Facades\DB::table('tb_perfil_itens')
                    ->where('ID_PERFIL', $user->ID_PERFIL)
                    ->where('ESTADO', 'True')
                    ->pluck('NOME')
                    ->toArray();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
            ],
            'flash' => [
                'message' => session('message'),
                'error' => session('error'),
            ],
        ];
    }
}
