<?php

namespace Modules\TripManagement\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MartFavorite extends Model
{
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(MartProduct::class, 'product_id');
    }
}
