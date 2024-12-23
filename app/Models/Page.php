<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Page extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    // Méthode utilitaire pour les chemins d'images
    public function getImageUrl()
    {
        return Storage::url($this->image_path);
    }

    public function getThumbnailUrl()
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    public function downloads()
    {
        return $this->morphMany(Download::class, 'downloadable');
    }
}
