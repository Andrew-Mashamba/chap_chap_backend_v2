<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Member;
use App\Models\Product;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $members = Member::where('status', 'active')->get();
        $products = Product::all();

        if ($members->isEmpty() || $products->isEmpty()) {
            return;
        }

        $orderStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        $paymentMethods = ['mpesa', 'cash', 'bank_transfer', 'wallet'];

        // Create sample orders
        foreach ($members as $member) {
            $numOrders = rand(1, 5);
            
            for ($i = 0; $i < $numOrders; $i++) {
                $product = $products->random();
                $quantity = rand(1, 5);
                $totalAmount = $product->selling_price * $quantity;
                $commission = ($totalAmount * $product->commission_rate) / 100;
                $status = $orderStatuses[array_rand($orderStatuses)];
                $createdAt = Carbon::now()->subDays(rand(1, 90));
                
                Order::create([
                    'member_id' => $member->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commission,
                    'status' => $status,
                    'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'delivery_address' => $member->shop_location,
                    'delivery_fee' => $product->within_region_delivery_fee,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'notes' => $status === 'cancelled' ? 'Customer cancelled the order' : null,
                    'created_at' => $createdAt,
                    'updated_at' => $status === 'completed' ? $createdAt->addDays(rand(1, 3)) : $createdAt,
                ]);
            }
        }
    }
}