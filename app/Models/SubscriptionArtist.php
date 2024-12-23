<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionArtist extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'plan_type',
        'start_date',
        'end_date',
        'status',
        'auto_renewal',
        'price_paid'
    ];

    protected $dates = [
        'start_date',
        'end_date'
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec la transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Vérifier si l'abonnement est actif
    public function isActive()
    {
        return $this->status === 'active' && $this->end_date > now();
    }

    // Vérifier si l'abonnement est sur le plan Pro
    public function isPro()
    {
        return $this->plan_type === 'pro' && $this->isActive();
    }

    // Renouveler l'abonnement
    public function renew($duration = 30) // durée en jours
    {
        $this->start_date = now();
        $this->end_date = now()->addDays($duration);
        $this->status = 'active';
        $this->save();
    }

    // Annuler l'abonnement
    public function cancel()
    {
        $this->status = 'cancelled';
        $this->auto_renewal = false;
        $this->save();
    }
}

