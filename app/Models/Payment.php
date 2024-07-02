<?php

namespace App\Models;

use App\Contracts\Completable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $order_id
 * @property int    $method
 * @property string $transaction_id
 * @property int    $click_paydoc_id
 * @property int    $amount
 * @property int    $state
 * @property int    $reason
 * @property string $payment_time
 * @property string $create_time
 * @property string $perform_time
 * @property string $cancel_time
 */
class Payment extends Model implements Completable
{
    protected $table = 'payments';

    protected $casts = [
        'order_id' => 'integer',
        'method' => 'integer',
        'amount' => 'integer',
        'state' => 'integer',
        'reason' => 'integer',
    ];

    public $timestamps = false;

    const METHOD_PAYME = 1;
    const METHOD_CLICK = 2;

    const STATE_CREATED                  =  1;
    const STATE_COMPLETED                =  2;
    const STATE_CANCELLED                = -1;
    const STATE_CANCELLED_AFTER_COMPLETE = -2;

    // Payment expiration time in milliseconds.
    const TIMEOUT = 43200000; // 12 hours
    const REASON_CANCELLED_BY_TIMEOUT = 4;

    const CREATED_AT = 'create_time';
    /**
     * Get order relationship
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Find payment by transaction_id
     */
    public static function findById($id)
    {
        return static::query()->where('transaction_id', $id)->first();
    }

    /**
     * Find payment by order_id
     */
    public static function findByOrderId($id)
    {
        return static::query()->where('order_id', $id)->first();
    }

    /**
     * Mark existing payment as completed
     */
    public function markAsCompleted()
    {
        $this->state        = static::STATE_COMPLETED;
        $this->perform_time = now();
        $this->save();
    }

    public function scopePaid($query)
    {
        return $query->where('state', static::STATE_COMPLETED);
    }
}
