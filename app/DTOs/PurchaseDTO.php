<?php

namespace App\DTOs;

class PurchaseDTO
{
    public readonly int $quantity;
    public readonly string $pack_type;
    
    public function __construct(array $data)
    {
        $this->quantity = $data['quantity'];
        $this->pack_type = $data['pack_type'];
    }
}