<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UtilizadorWeb extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'utilizadores_web';
    protected $primaryKey = 'ID_UTILIZADOR';
    public $timestamps = false;

    protected $fillable = [
        'ID_UTILIZADOR',
        'NOME_UTILIZADOR',
        'REMEMBER_TOKEN',
        'SENHA',
        'ACESSO',
        'ESTADO',
        'CREATED_AT',
        'ID_UTILIZADOR2',
        'ID_PERFIL',
        'ID_PESSOA',
    ];

    protected $hidden = [
        'SENHA',
    ];

    public function getAuthPassword()
    {
        return $this->SENHA;
    }

    public function getRememberTokenColumn()
    {
        return $this->REMEMBER_TOKEN ?? '';
    }

    public function getRememberTokenName(): string
    {
        return 'REMEMBER_TOKEN';
    }

    public static function hashPassword(string $password, string $token = ''): string
    {
        return strtolower(hash('sha512', $password . $token));
    }

    public static function generateToken(): string
    {
        return self::hashPassword((string) random_int(1000, 9999));
    }

    protected function casts(): array
    {
        return [
            //
        ];
    }

    public function setSenhaAttribute($value)
    {
        if (!empty($value)) {
            if (str_starts_with($value, '$2y$')) {
                $this->attributes['SENHA'] = $value;
                return;
            }

            $trimmed = trim($value);
            if (strlen($trimmed) === 128 && ctype_xdigit($trimmed)) {
                $this->attributes['SENHA'] = $trimmed;
                return;
            }

            $this->attributes['SENHA'] = $value;
        }
    }
}
