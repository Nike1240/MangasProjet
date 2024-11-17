<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;
    protected $fillable = ['name'];
    public function contents()
    {
        return $this->belongsToMany(Content::class, 'content_genre');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($genre) {
            $genre->slug = Str::slug($genre->name);
        });
    }
}
