<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'order_number',
        'total_amount',
        'commission_amount',
        'status',
        'delivery_address',
        'delivery_phone',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price', 'commission')
            ->withTimestamps();
    }
}
