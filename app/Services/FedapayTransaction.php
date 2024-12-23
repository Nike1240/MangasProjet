<?php

namespace App\Services;

use FedaPay\Transaction;
use FedaPay\Customer;


class FedaPayTransaction 
{
    public static function createTransaction(array $data)
    {
        $customer = Customer::create([
            'phone' => $data['customer']['phone']
        ]);

        $transaction = Transaction::create([
            'amount' => $data['amount'],
            'currency' => ['iso' => 'XOF'],
            'callback_url' => $data['callback_url'],
            'customer' => $customer
        ]);

        return $transaction;
    }

    public static function generatePaymentToken($transaction)
    {
        return $transaction->generateToken();
    }
}