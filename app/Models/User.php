<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword;
use App\Models\Client;
use App\Models\Artist;
use App\Models\Admin;


class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'role',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
   
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'suspended_at' => 'datetime'
    ];

    public function isSuspended()
    {
        return $this->status === 'suspended';
    }
    public function client()
    {
        return $this->hasOne(Client::class);
    }
    
    public function artist()
    {
        return $this->hasOne(Artist::class);
    }
     /**
     * Get the email address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function favorites()
    {
        return $this->morphMany(\App\Models\Favorite::class, 'favoritable');
    }
<<<<<<< HEAD
=======

>>>>>>> 63c78c9ab80a924a0181f9bda66fe2f6841a8b2e
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

<<<<<<< HEAD
    public function currentSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest()
            ->first();
    }
=======
    public function dkeyTransactions()
    {
        return $this->hasMany(DKeyTransaction::class);
    }

    public function dkeys()
    {
        return $this->hasMany(DKey::class);
    }

>>>>>>> 63c78c9ab80a924a0181f9bda66fe2f6841a8b2e
}
