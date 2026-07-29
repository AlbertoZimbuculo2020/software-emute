<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Sem autenticação.');
        }

        // Super admin (ACESSO=SIM) tem acesso a tudo
        if ($user->ACESSO === 'SIM') {
            return $next($request);
        }

        // Utilizador sem perfil definido não tem acesso
        if (empty($user->ID_PERFIL)) {
            abort(403, 'Sem perfil de acesso definido.');
        }

        // Carregar permissões do utilizador baseado no perfil
        $userPermissions = \Illuminate\Support\Facades\DB::table('tb_perfil_itens')
            ->where('ID_PERFIL', $user->ID_PERFIL)
            ->where('ESTADO', 'True')
            ->pluck('NOME')
            ->toArray();

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return $next($request);
            }
        }

        abort(403, 'Sem permissão para aceder a esta página.');
    }
}
