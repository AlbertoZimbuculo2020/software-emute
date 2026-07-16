<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'utilizador';
    protected $primaryKey = 'ID_UTILIZADOR';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'NOME_UTILIZADOR',
        'SENHA',
        'REMEMBER_TOKEN',
        'ESTADO',
        'ID_PERFIL',
        'ACESSO',
        'ID_PESSOA',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'SENHA',
    ];

    public function getAuthPassword()
    {
        return $this->SENHA;
    }

    /**
     * Get the remember token (used as salt for desktop-compatible SHA-512).
     */
    public function getRememberTokenColumn()
    {
        return $this->REMEMBER_TOKEN ?? '';
    }

    /**
     * Override to match the legacy column name (uppercase).
     */
    public function getRememberTokenName(): string
    {
        return 'REMEMBER_TOKEN';
    }

    /**
     * Hash password using the desktop algorithm: SHA512(password + token).
     * This matches the C# dalCriptografia.hash512() method.
     */
    public static function hashPassword(string $password, string $token = ''): string
    {
        return strtolower(hash('sha512', $password . $token));
    }

    /**
     * Generate a new token matching the desktop pattern:
     * SHA512(random 4-digit number).
     */
    public static function generateToken(): string
    {
        return self::hashPassword((string) random_int(1000, 9999));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            //
        ];
    }

    /**
     * Password attribute setter.
     * Detects if already hashed (Bcrypt or SHA-512 hex) and stores as-is.
     * Raw passwords should be hashed via User::hashPassword() before setting.
     */
    public function setSenhaAttribute($value)
    {
        if (!empty($value)) {
            // 1. If it's already a Bcrypt hash, just store it
            if (str_starts_with($value, '$2y$')) {
                $this->attributes['SENHA'] = $value;
                return;
            }

            // 2. If it's already a SHA-512 hex (128 chars), just store it
            $trimmed = trim($value);
            if (strlen($trimmed) === 128 && ctype_xdigit($trimmed)) {
                $this->attributes['SENHA'] = $trimmed;
                return;
            }

            // 3. Otherwise, store as-is (controller should hash with token first)
            $this->attributes['SENHA'] = $value;
        }
    }
}
