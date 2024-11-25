<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime'
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }

    // Scope pour les saisons publiées
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Méthode pour mettre à jour le compteur d'épisodes
    public function updateEpisodesCount()
    {
        $this->episodes_count = $this->episodes()->count();
        $this->save();
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likable');
    }
}
