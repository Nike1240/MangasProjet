<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guard = 'admin';
    
    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
