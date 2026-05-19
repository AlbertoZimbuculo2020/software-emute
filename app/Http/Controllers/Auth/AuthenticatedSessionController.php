<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $licencaAtiva = \Illuminate\Support\Facades\DB::table('licencas')
            ->where('ativado', true)
            ->where('data_fim', '>=', now()->format('Y-m-d'))
            ->orderBy('data_fim', 'desc')
            ->first();

        $licencaValida = false;
        $dataInicio = null;
        $dataFim = null;
        $plano = null;

        if ($licencaAtiva) {
            $licencaValida = true;
            $dataInicio = $licencaAtiva->data_inicio ? \Carbon\Carbon::parse($licencaAtiva->data_inicio)->format('d/m/Y') : null;
            $dataFim = $licencaAtiva->data_fim ? \Carbon\Carbon::parse($licencaAtiva->data_fim)->format('d/m/Y') : null;
            
            // Map plan to Portuguese label
            $plano = match ($licencaAtiva->plano) {
                'mensal' => 'Mensal',
                'semestral' => 'Semestral',
                'anual' => 'Anual',
                default => ucfirst($licencaAtiva->plano),
            };
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'licencaValida' => $licencaValida,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'plano' => $plano,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $licencaAtiva = \Illuminate\Support\Facades\DB::table('licencas')
            ->where('ativado', true)
            ->where('data_fim', '>=', now()->format('Y-m-d'))
            ->exists();

        if (!$licencaAtiva) {
            return back()->withErrors([
                'login' => 'A sua licença expirou ou não está ativa. Por favor, ative uma licença válida para entrar.',
            ]);
        }

        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
    /**
     * Test the database connection with provided credentials.
     */
    public function testConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'nullable|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $default = config('database.default');
        
        // Temporarily change config
        config([
            "database.connections.test_connection" => array_merge(
                config("database.connections.{$default}") ?? [],
                [
                    'host' => $request->db_host,
                    'port' => $request->db_port ?? config("database.connections.{$default}.port"),
                    'database' => $request->db_database,
                    'username' => $request->db_username,
                    'password' => $request->db_password ?? '',
                ]
            )
        ]);

        try {
            \Illuminate\Support\Facades\DB::connection('test_connection')->getPdo();
            return response()->json([
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na conexão: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Save the database connection details to the session.
     */
    public function saveConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'nullable|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $request->session()->put('db_host', $request->db_host);
        $request->session()->put('db_port', $request->db_port);
        $request->session()->put('db_database', $request->db_database);
        $request->session()->put('db_username', $request->db_username);
        $request->session()->put('db_password', $request->db_password);

        // Run migrations automatically to ensure database is ready
        try {
            // Apply the config temporarily to run migrations
            $default = config('database.default');
            config([
                "database.connections.{$default}.host" => $request->db_host,
                "database.connections.{$default}.port" => $request->db_port,
                "database.connections.{$default}.database" => $request->db_database,
                "database.connections.{$default}.username" => $request->db_username,
                "database.connections.{$default}.password" => $request->db_password,
            ]);
            \Illuminate\Support\Facades\DB::purge($default);
            
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            
            $message = 'Configurações guardadas e Base de Dados actualizada com sucesso!';
        } catch (\Exception $e) {
            $message = 'Configurações guardadas, mas falha ao migrar base de dados: ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
