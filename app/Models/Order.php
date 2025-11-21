<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use softDeletes;

    protected $fillable = [
        'code',
        'user_id',
        'responsible_user_id',
        'pickup_address_id',
        'delivery_address_id',
        'status',
        'payment_status',
        'total_qty',
        'subtotal_amount',
        'delivery_fee',
        'total_amount',
        'notes',
        'completed_date',
    ];

//    public function setCodeAttribute(): void
//    {
//        $this->attributes['code'] = "LNDRY" . Carbon::now()->format("ymd") . str_pad($this->id, 4, '0', STR_PAD_LEFT);
//    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'pickup_address_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function feedbacks(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }
}
