<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContentProgression extends Model
{
    protected $guarded = ['id'];
    use HasFactory;

    // Dans le modèle UserContentProgression
public function content()
{
    return $this->belongsTo(Content::class);
}

}
