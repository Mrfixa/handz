<?php

namespace Modules\TripManagement\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MartProduct extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount_price',
        'unit',
        'image',
        'category',
        'is_active',
        'is_featured',
        'is_popular',
        'sold_count',
        'stock',
        'zone_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'sold_count' => 'integer',
        'stock' => 'integer',
    ];

    /** Effective unit price the customer pays (sale price when set and lower). */
    public function getEffectivePriceAttribute(): float
    {
        $discount = $this->discount_price !== null ? (float) $this->discount_price : null;
        if ($discount !== null && $discount > 0 && $discount < (float) $this->price) {
            return $discount;
        }
        return (float) $this->price;
    }

    public function orderItems()
    {
        return $this->hasMany(MartOrderItem::class, 'product_id');
    }
}
