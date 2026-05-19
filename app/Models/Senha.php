<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Senha extends Model
{
    protected $table = 'tb_senha';
    
    protected $primaryKey = 'Id';

    protected $fillable = [
        'Codigo',
        'Tipo',
        'Estado',
        'Guiche',
        'DataCriacao',
        'DataChamada',
        'DataUltimaChamada'
    ];

    protected $casts = [
        'DataCriacao' => 'date',
        'DataChamada' => 'datetime',
        'DataUltimaChamada' => 'datetime'
    ];
}
