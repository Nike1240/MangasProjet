<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    // Définir les attributs modifiables (mass assignable)
    protected $guarded = ['id'];

    /**
     * Relation avec le modèle PackageType
     * Un package appartient à un type de package.
     */
    public function packageType()
    {
        return $this->belongsTo(PackageType::class);
    }


    /**
     * Vérifier si le package est actif
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Vérifier si le package est un pack avec prix fixe
     * @return bool
     */
    public function isPack()
    {
        return $this->is_pack;
    }


    public function dkeyTransaction()
    {
        return $this->hasMany(DKeyTransaction::class);
    }
}

