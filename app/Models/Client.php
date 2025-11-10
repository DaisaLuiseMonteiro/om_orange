<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nom',
        'prenom',
        'date_naissance',
        'adresse',
        'telephone',
        'cni',
        'marchand_id'
    ];

    /**
     * Relation avec l'utilisateur (polymorphique)
     */
    public function user()
    {
        return $this->morphOne(User::class, 'authenticatable');
    }

    /**
     * Relation avec les comptes du client
     */
    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    /**
     * Relation avec le marchand
     */
    public function marchand()
    {
        return $this->belongsTo(Marchand::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($client) {
            if (empty($client->id)) {
                $client->id = (string) Str::uuid();
            }
        });
    }
}
