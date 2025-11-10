<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marchand extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'telephone',
        'code_marchand'
    ];

    /**
     * Get the clients for the marchand.
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
