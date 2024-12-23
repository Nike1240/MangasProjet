<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageType extends Model
{
    use HasFactory;

    // Définir les attributs modifiables (mass assignable)
    protected $guarded = ['id'];
    // protected $fillable = ['name', 'description'];

    /**
     * Relation avec le modèle Package
     * Un type de package peut avoir plusieurs packages.
     */
    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}

