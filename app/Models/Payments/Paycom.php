<?php

namespace App\Models\Payments;

use App\Helpers\Format;
use App\Http\Requests\Payment\PaycomRequest;
use App\Models\Payment;
use Carbon\Carbon;

class Paycom extends Payment
{
    /**
     * Create new payment
     */
    public static function createPayment(PaycomRequest $request)
    {
        $payment = new Paycom;

        $payment->method            = static::METHOD_PAYME;
        $payment->order_id         = $request->order_id;
        $payment->transaction_id    = $request->transaction_id;
        $payment->amount            = $request->amount / 100;
        $payment->state             = static::STATE_CREATED;
        $payment->payment_time      = Format::toDatetime($request->payment_time);
        $payment->create_time       = Carbon::now();

        $payment->save();

        return $payment;
    }

    /**
     * Check whether payment has been expired
     */
    public function isExpired()
    {
        return $this->state == static::STATE_CREATED &&
            Carbon::now()->diffInMilliseconds($this->create_time) > static::TIMEOUT;
    }

    /**
     * Cancel payment
     */
    public function cancel($reason)
    {
        // Get current $cancel_time
        $this->cancel_time = Carbon::now();

        if ($this->state == static::STATE_COMPLETED) {
            // Scenario: CreateTransaction -> PerformTransaction -> CancelTransaction
            $this->state = static::STATE_CANCELLED_AFTER_COMPLETE;
        } else {
            // Scenario: CreateTransaction -> CancelTransaction
            $this->state = static::STATE_CANCELLED;
        }

        $this->reason = $reason;
        $this->save();
    }

    /**
     * Generate checkout URL
     * 
     * @param int $order_id
     * @param int $amount сумма чека в ТИИНАХ
     * @param string $callback
     * 
     * @return string
     */
    public static function checkout($order_id, $amount, $callback = null)
    {
        $checkout_url = config('paycom.checkout_url');
        $merchant_id = config('paycom.merchant_id');
        $callback = $callback ?: url('/');

        return $checkout_url . base64_encode("m={$merchant_id};ac.order_id={$order_id};a={$amount};c={$callback}");
    }
}
