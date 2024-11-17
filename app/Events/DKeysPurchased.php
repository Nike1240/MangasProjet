<?php

namespace App\Events;

class DKeysPurchased implements ShouldBroadcast
{
    public $userId;
    public $quantity;
    
    public function __construct($userId, $quantity)
    {
        $this->userId = $userId;
        $this->quantity = $quantity;
    }
}