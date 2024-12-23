<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;


class Episode extends Model
{
    use HasFactory;

    use SoftDeletes;
    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
        'duration' => 'integer'
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function favorites()
{
    return $this->morphMany(Favorite::class, 'favoritable');
}
}
