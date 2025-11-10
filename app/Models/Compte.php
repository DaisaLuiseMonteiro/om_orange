<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'numero_compte',
        'user_id',
        'titulaire',
        'type',
        'solde',
        'devise',
        'statut',
        'motif_blocage',
        'metadata'
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (empty($model->numero_compte)) {
                $model->numero_compte = 'C' . str_pad(static::withTrashed()->count() + 1, 8, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relation avec l'utilisateur propriétaire du compte
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
