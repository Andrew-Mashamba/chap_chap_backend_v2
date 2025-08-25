<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'punguzo_product_id',
        'name',
        'description',
        'sku',
        'barcode',
        'category',
        'subcategory',
        'brand',
        'unit',
        'weight',
        'dimensions',
        'merchant_id',
        'merchant_name',
        'pickup_locations',
        'shop_region',
        'region',
        'selling_price',
        'original_price',
        'discount_price',
        'stock_quantity',
        'total_item_available',
        'min_stock_level',
        'max_stock_level',
        'within_region_delivery_fee',
        'outside_region_delivery_fee',
        'is_delivery_allowed',
        'is_active',
        'is_featured',
        'media',
        'media_json',
        'tags',
        'raw_json',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'total_item_available' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'merchant_id' => 'integer',
        'within_region_delivery_fee' => 'decimal:2',
        'outside_region_delivery_fee' => 'decimal:2',
        'is_delivery_allowed' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'media' => 'array',
        'media_json' => 'array',
        'tags' => 'array',
        'raw_json' => 'array',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot('quantity', 'price', 'commission')
            ->withTimestamps();
    }
}
