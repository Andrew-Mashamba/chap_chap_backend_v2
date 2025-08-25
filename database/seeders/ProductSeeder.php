<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'name' => 'Smartphone X',
                'description' => 'Latest smartphone with advanced features',
                'category' => 'Electronics',
                'selling_price' => 899.99,
                'commission_rate' => 5.00,
                'total_item_available' => 50,
                'merchant_name' => 'Tech Store',
                'merchant_phone' => '255123456792',
                'merchant_email' => 'tech@example.com',
                'within_region_delivery_fee' => 5.00,
                'outside_region_delivery_fee' => 10.00,
            ],
            [
                'name' => 'Laptop Pro',
                'description' => 'High-performance laptop for professionals',
                'category' => 'Electronics',
                'selling_price' => 1299.99,
                'commission_rate' => 7.00,
                'total_item_available' => 30,
                'merchant_name' => 'Tech Store',
                'merchant_phone' => '255123456792',
                'merchant_email' => 'tech@example.com',
                'within_region_delivery_fee' => 8.00,
                'outside_region_delivery_fee' => 15.00,
            ],
            [
                'name' => 'Running Shoes',
                'description' => 'Comfortable running shoes for athletes',
                'category' => 'Sports',
                'selling_price' => 79.99,
                'commission_rate' => 4.00,
                'total_item_available' => 100,
                'merchant_name' => 'Sports World',
                'merchant_phone' => '255123456793',
                'merchant_email' => 'sports@example.com',
                'within_region_delivery_fee' => 3.00,
                'outside_region_delivery_fee' => 7.00,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
