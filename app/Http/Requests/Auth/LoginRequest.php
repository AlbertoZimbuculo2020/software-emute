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

        $login = trim($this->login);
        $senha = $this->senha;

        // Search for user (Ignoring status for debug)
        $user = \App\Models\User::where('NOME_UTILIZADOR', 'LIKE', $login)
            ->first();

        if (! $user) {
            $this->fail('Usuário não encontrado.');
        }

        // Check password using Hybrid Strategy (Bcrypt OR Multi-Legacy SHA-512)
        $authenticated = false;
        
        $storedHash = trim((string)$user->SENHA);

        // 1. Try Bcrypt (Laravel default)
        if (\Illuminate\Support\Str::startsWith($storedHash, '$2y$')) {
            if (\Illuminate\Support\Facades\Hash::check($senha, $storedHash)) {
                $authenticated = true;
            }
        } 
        
        // 2. Try Legacy SHA-512 Strategies
        if (!$authenticated) {
            $hashedPlain = strtolower(hash('sha512', (string)$senha));
            $hashedUTF16LE = strtolower(hash('sha512', mb_convert_encoding((string)$senha, 'UTF-16LE')));
            
            $storedHashLower = strtolower($storedHash);

            if ($storedHashLower === $hashedPlain) {
                $authenticated = true;
            } elseif ($storedHashLower === $hashedUTF16LE) {
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
