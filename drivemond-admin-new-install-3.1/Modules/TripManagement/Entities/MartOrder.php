<?php

namespace Modules\TripManagement\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ChattingManagement\Entities\ChannelConversation;
use Modules\ChattingManagement\Entities\ChannelList;
use Modules\UserManagement\Entities\User;

class MartOrder extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The full set of mart order statuses, in lifecycle order.
     */
    public const STATUSES = ['pending', 'accepted', 'picked_up', 'delivered', 'cancelled'];

    /**
     * Allowed status transitions: target status => list of statuses it may come from.
     * Single source of truth shared by the driver API and the admin panel so both
     * can never drive an order into an inconsistent state.
     */
    public const STATUS_TRANSITIONS = [
        'accepted'  => ['pending'],
        'picked_up' => ['accepted'],
        'delivered' => ['picked_up'],
        'cancelled' => ['pending', 'accepted'],
    ];

    protected $fillable = [
        'ref_id',
        'customer_id',
        'driver_id',
        'status',
        'total_amount',
        'tip_amount',
        'discount_amount',
        'promo_code',
        'payment_status',
        'payment_method',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'driver_lat',
        'driver_lng',
        'signature_image',
        'delivery_photo',
        'notes',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_lat' => 'decimal:7',
        'delivery_lng' => 'decimal:7',
        'driver_lat' => 'decimal:7',
        'driver_lng' => 'decimal:7',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items()
    {
        return $this->hasMany(MartOrderItem::class, 'order_id');
    }

    public function channel(): MorphOne
    {
        return $this->morphOne(ChannelList::class, 'channelable');
    }

    public function conversations(): MorphMany
    {
        return $this->morphMany(ChannelConversation::class, 'convable');
    }
}
