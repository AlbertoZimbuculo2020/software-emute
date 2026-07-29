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
            // Carrega atributos extra do perfil e da entidade (medico/enfermeira/etc.)
            $extra = \Illuminate\Support\Facades\DB::table('utilizadores_web as u')
                ->leftJoin('tb_perfil as p', 'u.ID_PERFIL', '=', 'p.ID')
                ->leftJoin('tb_tipoentidade as te', 'u.ID_PESSOA', '=', 'te.Codigo')
                ->where('u.ID_UTILIZADOR', $user->ID_UTILIZADOR)
                ->select([
                    'p.PERFIL as PERFIL_DESC',
                    'te.Nome as PESSOA_NOME',
                    'te.TipoEntidade as TIPO_ENTIDADE',
                    'te.Estado as PESSOA_ESTADO',
                ])
                ->first();

            if ($extra) {
                foreach ((array)$extra as $key => $value) {
                    if ($value !== null && !isset($user->{$key})) {
                        $user->setAttribute($key, $value);
                    }
                }
            }

            // Fallback name para compatibilidade com UI (AutenticatedLayout legado)
            if (!isset($user->name) || $user->name === null) {
                $user->setAttribute('name', $user->PESSOA_NOME ?? $user->NOME_UTILIZADOR ?? '');
            }
            if (!isset($user->email) || $user->email === null) {
                $user->setAttribute('email', mb_strtolower(($user->NOME_UTILIZADOR ?? 'user')) . '@emute.local');
            }

            // Limpar estado dirty para evitar que atributos virtuais (PERFIL_DESC, PESSOA_NOME, name, email, etc.)
            // sejam persistidos na tabela `utilizador` quando o model for salvo (ex: ciclo do remember_token).
            $user->syncOriginal();

            // Se for Admin (ACESSO=SIM), tem acesso a tudo
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
            'clinicData' => (function() {
                try {
                    $empresa = \Illuminate\Support\Facades\DB::table('tb_empresa')->first();
                } catch (\Exception $e) {
                    $empresa = null;
                }
                $logo = null;
                if ($empresa && !empty($empresa->IMAGEM)) {
                    $teste = @getimagesizefromstring($empresa->IMAGEM);
                    if ($teste) {
                        $mime = $teste['mime'];
                        $logo = 'data:' . $mime . ';base64,' . base64_encode($empresa->IMAGEM);
                    }
                }
                if (!$logo) {
                    try {
                        $logo = route('empresa.logo');
                    } catch (\Exception $e) {
                        $logo = null;
                    }
                }
                return [
                    'nome' => $empresa->DESCRICAO ?? 'EMUTE',
                    'logo' => $logo,
                    'logoUrl' => route('empresa.logo'),
                ];
            })(),
        ];
    }
}
