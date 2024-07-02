<?php

namespace App\Models\Payments;

use App\Models\Payment;

class Click extends Payment
{
    /**
     * Create new payment
     */
    public static function createPayment(array $request)
    {
        $payment = new Click;

        $payment->method            = static::METHOD_CLICK;
        $payment->order_id         = $request['merchant_trans_id'];
        $payment->transaction_id    = $request['click_trans_id'];
        $payment->click_paydoc_id   = $request['click_paydoc_id'];
        $payment->amount            = $request['amount'];
        $payment->state             = static::STATE_CREATED;
        $payment->payment_time      = $request['sign_time'];
        $payment->create_time       = now();

        $payment->save();

        return $payment;
    }

    /**
     * Generate checkout URL
     * 
     * @param int $order_id
     * @param int $amount в суммах
     * @param string $callback
     * 
     * @return string
     */
    public static function checkout($order_id, $amount, $callback = null)
    {
        $checkout_url = config('click.checkout_url');
        $service_id = config('click.service_id');
        $merchant_id = config('click.merchant_id');

        $callback = $callback ?: url('/');

        return $checkout_url . "?service_id={$service_id}&merchant_id={$merchant_id}&amount={$amount}&transaction_param={$order_id}&return_url={$callback}";
    }
}
