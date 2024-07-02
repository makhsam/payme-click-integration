<?php

namespace App\Models;

use App\Contracts\Completable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $user_id
 * @property int    $billable_id
 * @property int    $subtotal
 * @property int    $total
 * @property int    $discount
 * @property int    $state
 * @property int    $created_at
 * @property int    $approved_at
 * @property int    $expires_at
 * @property string $billable_type
 */
class Order extends Model implements Completable
{
    // Order states
    const STATE_CREATED = 0;
    const STATE_WAITING_PAY = 1;
    const STATE_PAY_ACCEPTED = 2;
    const STATE_CANCELLED = -1;
    const STATE_CANCELLED_AFTER_PAID = -2;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'billable_type',
        'billable_id',
        'subtotal',
        'total',
        'discount',
        'state',
        'created_at',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'billable_type' => 'string',
        'billable_id' => 'integer',
        'subtotal' => 'integer',
        'total' => 'integer',
        'discount' => 'integer',
        'state' => 'integer',
        'approved_at' => 'timestamp',
        'expires_at' => 'timestamp',
    ];

    // Relations:
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function billable()
    {
        return $this->morphTo('billable');
    }

    // Functions:
    public function markAsCompleted()
    {
        $this->state = self::STATE_PAY_ACCEPTED;
        $this->save();

        $this->billable->complete($this->user_id);
    }

    /**
     * Cancel the order
     */
    public function cancel()
    {
        $this->state = self::STATE_CANCELLED;
        $this->save();
    }

    public function cancelAfterCompleted()
    {
        $this->state = self::STATE_CANCELLED_AFTER_PAID;
        $this->save();

        $this->billable->cancel($this->user_id);
    }
}
