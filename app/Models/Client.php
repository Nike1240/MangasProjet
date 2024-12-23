<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;



class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'is_active',
    ];

    public function user()
{
    return $this->belongsTo(User::class, 'id');
}
  
}