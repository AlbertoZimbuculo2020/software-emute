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
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
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
}
