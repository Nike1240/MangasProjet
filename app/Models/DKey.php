<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DKey extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Scope a query to only include truly active keys.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        // D'abord, mettre à jour les clés expirées
        DB::table('d_keys')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        // Ensuite, retourner uniquement les clés réellement actives
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::retrieved(function ($dKey) {
            if ($dKey->status === 'active' && $dKey->expires_at <= now()) {
                $dKey->status = 'expired';
                $dKey->save();
            }
        });
    }
}
