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
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
   
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function dkeyTransactions()
    {
        return $this->hasMany(DKeyTransaction::class);
    }

    public function dkeys()
    {
        return $this->hasMany(DKey::class);
    }

    public function downloads()
    {
        return $this->hasMany(Download::class);
    }


    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function hasActiveSubscription()
    {
        $subscription = $this->subscription;
        return $subscription && $subscription->isActive();
    }
}
