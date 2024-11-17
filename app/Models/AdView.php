<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdView extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    // protected $fillable = [
    //     'user_id',
    //     'advertisement_id', // Changé de video_id
    //     'watched_duration',
    //     'reward_earned'
    // ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
