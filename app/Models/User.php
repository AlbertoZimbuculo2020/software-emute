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
     * Enforce SHA-512 encryption for passwords to match legacy database and desktop system.
     */
    public function setSenhaAttribute($value)
    {
        if (!empty($value)) {
            // If it's already a SHA-512 hex (128 chars), don't re-hash
            if (strlen($value) === 128 && ctype_xdigit($value)) {
                $this->attributes['SENHA'] = $value;
            } else {
                $this->attributes['SENHA'] = hash('sha512', $value);
            }
        }
    }
}
