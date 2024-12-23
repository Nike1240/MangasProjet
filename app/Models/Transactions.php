<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $fillable = [
        'description',
        'amount',
        'firstname',
        'lastname',
        'fedapay_id',
        'status',
        'currency'
    ];
}
