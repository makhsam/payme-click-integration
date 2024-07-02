<?php

namespace App\Http\Requests\Payment;

use App\Helpers\Format;
use App\Models\Payments\Paycom;

class PaycomRequest
{
    public $id;
    public $order_id;
    public $transaction_id;
    public $payment_time;
    public $amount;
    public $reason;

    public function __construct(array $request)
    {
        $this->id               = $request['id'];
        $this->order_id        = $request['params']['account']['order_id'] ?? null;
        $this->transaction_id   = $request['params']['id'] ?? null;
        $this->payment_time     = $request['params']['time'] ?? null;
        $this->amount           = $request['params']['amount'] ?? null;
        $this->reason           = $request['params']['reason'] ?? null;
    }

    public function isExpired()
    {
        return abs($this->payment_time - Format::currentTime(true)) >= Paycom::TIMEOUT;
    }
}
