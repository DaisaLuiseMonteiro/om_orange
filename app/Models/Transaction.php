<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reference',
        'type',
        'montant',
        'devise',
        'compte_emetteur_id',
        'compte_destinataire_id',
        'frais',
        'statut',
        'motif',
        'metadata',
        'date_execution'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'date_execution' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (empty($model->reference)) {
                $model->reference = 'TR' . date('Ymd') . strtoupper(Str::random(6));
            }
        });
    }

    /**
     * Compte émetteur de la transaction
     */
    public function emetteur()
    {
        return $this->belongsTo(Compte::class, 'compte_emetteur_id');
    }

    /**
     * Compte destinataire de la transaction
     */
    public function destinataire()
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }
}
