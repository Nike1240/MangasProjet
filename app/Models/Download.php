<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function downloadable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope pour filtrer les téléchargements du mois en cours
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('downloaded_at', now()->month);
    }
}
