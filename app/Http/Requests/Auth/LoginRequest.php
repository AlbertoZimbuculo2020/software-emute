<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'senha' => ['required', 'string'],
            'db_host' => ['nullable', 'string'],
            'db_port' => ['nullable', 'string'],
            'db_database' => ['nullable', 'string'],
            'db_username' => ['nullable', 'string'],
            'db_password' => ['nullable', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Dynamic Database Configuration
        if ($this->db_host) {
            $default = config('database.default');
            $config = [
                "database.connections.{$default}.host" => $this->db_host,
                "database.connections.{$default}.port" => $this->db_port ?? config("database.connections.{$default}.port"),
                "database.connections.{$default}.database" => $this->db_database ?? config("database.connections.{$default}.database"),
                "database.connections.{$default}.username" => $this->db_username ?? config("database.connections.{$default}.username"),
                "database.connections.{$default}.password" => $this->db_password ?? '',
            ];

            config($config);
            \Illuminate\Support\Facades\DB::purge($default);

            try {
                \Illuminate\Support\Facades\DB::connection($default)->getPdo();
                // Persist settings in session for middleware
                session([
                    'db_host' => $config["database.connections.{$default}.host"],
                    'db_port' => $config["database.connections.{$default}.port"],
                    'db_database' => $config["database.connections.{$default}.database"],
                    'db_username' => $config["database.connections.{$default}.username"],
                    'db_password' => $config["database.connections.{$default}.password"],
                ]);
            } catch (\Exception $e) {
                throw ValidationException::withMessages([
                    'login' => 'Falha ao conectar à base de dados: ' . $e->getMessage(),
                ]);
            }
        }

        $login = trim($this->login);
        $senha = $this->senha;

        // Search for user (Ignoring status for debug)
        $user = \App\Models\User::where('NOME_UTILIZADOR', 'LIKE', $login)
            ->first();

        if (! $user) {
            $this->fail('Usuário não encontrado.');
        }

        // Check password using Hybrid Strategy (Bcrypt OR Desktop-Compatible SHA-512 with token)
        $authenticated = false;
        
        $storedHash = trim((string)$user->SENHA);
        $token = $user->REMEMBER_TOKEN ?? '';

        // 1. Try Bcrypt (Laravel default)
        if (\Illuminate\Support\Str::startsWith($storedHash, '$2y$')) {
            if (\Illuminate\Support\Facades\Hash::check($senha, $storedHash)) {
                $authenticated = true;
            }
        } 
        
        // 2. Try Desktop-Compatible SHA-512: SHA512(password + token)
        if (!$authenticated) {
            $hashedWithToken = \App\Models\User::hashPassword((string)$senha, $token);
            
            $storedHashLower = strtolower($storedHash);

            if ($storedHashLower === $hashedWithToken) {
                $authenticated = true;
            }
        }

        if (! $authenticated) {
            $this->fail('Senha incorreta.');
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    protected function fail(string $message): void
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => $message,
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
