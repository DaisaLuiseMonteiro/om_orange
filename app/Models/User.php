<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Relation polymorphique avec les modèles authentifiables (Client, Admin, etc.)
     */
    public function authenticatable()
    {
        return $this->morphTo();
    }

    /**
     * Vérifie si l'utilisateur est un client
     */
    public function isClient()
    {
        return $this->authenticatable_type === Client::class;
    }

    /**
     * Vérifie si l'utilisateur est un administrateur
     */
    public function isAdmin()
    {
        return $this->authenticatable_type === Admin::class;
    }

    /**
     * Récupère le rôle de l'utilisateur
     */
    public function getRoleAttribute()
    {
        if ($this->isAdmin()) {
            return 'admin';
        } elseif ($this->isClient()) {
            return 'client';
        } else {
            return 'guest';
        }
    }

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'authenticatable_type',
        'authenticatable_id',
        'verification_code',
        'code_expires_at',
        'is_active',
        'code_secret'
    ];

    /**
     * Les attributs qui doivent être cachés pour la sérialisation.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'code_secret'
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'code_expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];
}
