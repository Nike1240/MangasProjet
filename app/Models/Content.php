<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = ['id'];

    // Relations communes
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    // Relations spécifiques au manga
    public function chapters()
    {
        return $this->hasMany(Chapter::class)->when(
            $this->type === 'manga',
            fn($query) => $query->orderBy('number')
        );
    }

    public function seasons()
    {
        return $this->hasMany(Season::class)->when(
            $this->type === 'anime',
            fn($query) => $query->orderBy('number')
        );
    }

    // Relations spécifiques à l'anime
    public function episodes()
    {
        return $this->hasMany(Episode::class)->when(
            $this->type === 'anime',
            fn($query) => $query->orderBy('season_number')->orderBy('episode_number')
        );
    }

    // Scopes utiles
    public function scopeMangas($query)
    {
        return $query->where('type', 'manga');
    }

    public function scopeAnimes($query)
    {
        return $query->where('type', 'anime');
    }

        public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
{
    return $this->morphMany(Favorite::class, 'favoritable');
}
}
