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
                try {
                    $permissions = \Illuminate\Support\Facades\DB::table('tb_perfil_itens')
                        ->where('ID_PERFIL', $user->ID_PERFIL)
                        ->where('ESTADO', 'True')
                        ->pluck('NOME')
                        ->toArray();
                } catch (\Exception $e) {
                    $permissions = [];
                }
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
            'empresa' => (function() {
                $empresa = \Illuminate\Support\Facades\DB::table('tb_empresa')->first();
                if (!$empresa) return null;
                return [
                    'nome' => $empresa->DESCRICAO,
                    'logo' => $empresa->IMAGEM ? 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM) : null
                ];
            })(),
        ];
    }
}
