<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;
use Carbon\Carbon;


class Artist extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime'

    ];
    protected static function booted()
    {
        static::updated(function ($artist) {
            // Trouver l'utilisateur par l'ID de l'artiste
            $user = User::find($artist->user_id);
            
            if ($user) {
                $user->status = $artist->is_active ? 'active' : 'inactive';
                $user->save();
            }
        });
    }

    public function isAccountActive()
    {
        return $this->is_active && is_null($this->deactivated_at);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function contents()
    {
        return $this->hasMany(Content::class);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }
  
}


    

