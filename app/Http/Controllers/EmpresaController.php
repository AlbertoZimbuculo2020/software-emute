<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmpresaController extends Controller
{
    public function logo(Request $request)
    {
        $row = DB::table('tb_empresa')->select('IMAGEM')->first();

        if ($row && !empty($row->IMAGEM)) {
            $bin = $row->IMAGEM;
            $mime = 'image/jpeg';
            $sig = substr($bin, 0, 4);
            if (str_starts_with($sig, "\x89PNG")) $mime = 'image/png';
            elseif (str_starts_with($sig, "GIF8")) $mime = 'image/gif';
            elseif (str_starts_with($sig, "BM"))   $mime = 'image/bmp';
            elseif (str_starts_with($sig, "<?xml") || str_starts_with(ltrim($sig), '<svg')) $mime = 'image/svg+xml';

            // Validar se a imagem é realmente válida antes de retornar
            $imagemValida = @getimagesizefromstring($bin);
            if ($imagemValida !== false) {
                return response($bin, 200)
                    ->header('Content-Type', $mime)
                    ->header('Cache-Control', 'public, max-age=86400');
            }
        }

        $fallback = public_path('images/logo.png');
        if (file_exists($fallback)) {
            return response()->file($fallback, ['Cache-Control' => 'public, max-age=86400']);
        }

        abort(404);
    }

    public function create()
    {
        try {
            $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return Inertia::render('Error/DatabaseConnection', [
                'message' => 'A conexão não foi feita ao banco de dados.'
            ]);
        }

        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        return Inertia::render('Empresa/Register', [
            'empresa' => $empresa
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'DESCRICAO' => 'required|string|max:100',
            'NIF' => 'required|string|max:100',
            'NUMEROCOMERCIAL' => 'nullable|string|max:100',
            'REGIME' => 'nullable|string|max:200',
            'TELEFONE' => 'nullable|string|max:200',
            'TELEFONE2' => 'nullable|string|max:200',
            'EMAIL' => 'nullable|email|max:200',
            'PROVINCIA' => 'nullable|string|max:200',
            'CIDADE' => 'nullable|string|max:200',
            'RUA' => 'nullable|string|max:200',
            'INDICATIVO' => 'nullable|string|max:10',
            'IMAGEM' => 'nullable|image|max:4096',
            'LOGIN' => 'nullable|string|max:200',
            'SENHA' => 'nullable|string|min:4'
        ]);

        $empresaData = collect($data)->except(['IMAGEM', 'LOGIN', 'SENHA'])->toArray();
        $empresaData['ESTADO'] = 'Activado';
        $empresaData['CREATED_AT'] = now();

        if ($request->hasFile('IMAGEM')) {
            $empresaData['IMAGEM'] = file_get_contents($request->file('IMAGEM')->getRealPath());
        }

        // Setup the initial admin user if credentials provided
        $userId = 1;
        if (!empty($data['LOGIN']) && !empty($data['SENHA'])) {
            $userExists = DB::table('utilizador')->where('NOME_UTILIZADOR', $data['LOGIN'])->first();
            if (!$userExists) {
                // Generate token and hash password with it (desktop-compatible)
                $token = \App\Models\User::generateToken();
                $userId = DB::table('utilizador')->insertGetId([
                    'NOME_UTILIZADOR' => $data['LOGIN'],
                    'SENHA' => \App\Models\User::hashPassword($data['SENHA'], $token),
                    'REMEMBER_TOKEN' => $token,
                    'ESTADO' => 'Activado',
                    'ACESSO' => 'SIM',
                    'ID_PERFIL' => 1
                ]);
            } else {
                // Generate new token and hash password with it (desktop-compatible)
                $token = \App\Models\User::generateToken();
                DB::table('utilizador')->where('ID_UTILIZADOR', $userExists->ID_UTILIZADOR)->update([
                    'SENHA' => \App\Models\User::hashPassword((string)$data['SENHA'], $token),
                    'REMEMBER_TOKEN' => $token
                ]);
                $userId = $userExists->ID_UTILIZADOR;
            }
        } else {
            $firstUser = DB::table('utilizador')->first();
            if ($firstUser) {
                $userId = $firstUser->ID_UTILIZADOR;
            }
        }

        $empresaData['ID_UTILIZADOR'] = $userId;

        $empresaExists = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->exists();
        if ($empresaExists) {
            DB::table('tb_empresa')->where('ID_EMPRESA', 1)->update($empresaData);
        } else {
            $empresaData['ID_EMPRESA'] = 1;
            DB::table('tb_empresa')->insert($empresaData);
        }

        // Criar licença grátis de 3 meses automaticamente
        $jaTemLicenca = DB::table('licencas')
            ->where('nif', $data['NIF'] ?? '')
            ->where('ativado', true)
            ->where('data_fim', '>=', Carbon::now()->format('Y-m-d'))
            ->exists();

        if (!$jaTemLicenca) {
            do {
                $codigo = str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            } while (DB::table('licencas')->where('codigo_ativacao', $codigo)->exists());

            $dataInicio = Carbon::now();
            $dataFim = Carbon::now()->addMonths(3);

            DB::table('licencas')->insert([
                'email' => $data['EMAIL'] ?? 'admin@emute.co.ao',
                'empresa' => strtoupper($data['DESCRICAO'] ?? ''),
                'nif' => $data['NIF'] ?? '',
                'plano' => 'trial',
                'codigo_ativacao' => $codigo,
                'ativado' => true,
                'data_inicio' => $dataInicio->format('Y-m-d'),
                'data_fim' => $dataFim->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('login')->with('success', 'Configurações gravadas com sucesso! Licença grátis de 3 meses ativada.');
    }
}
