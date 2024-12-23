<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $dates = [
        'start_date',
        'end_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function isActive()
    {
        return $this->status === 'active' && 
               Carbon::now()->between($this->start_date, $this->end_date);
    }

    public function hasDownloadLimitReached(User $user)
    {
        $downloadsThisMonth = Download::where('user_id', $user->id)
            ->whereMonth('downloaded_at', now()->month)
            ->count();

        return $downloadsThisMonth >= $this->download_limit;
    }

}
