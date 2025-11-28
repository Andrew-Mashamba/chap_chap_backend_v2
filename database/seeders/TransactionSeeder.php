<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Member;
use App\Models\Order;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        $members = Member::where('status', 'active')->get();
        $completedOrders = Order::where('status', 'completed')->get();

        if ($members->isEmpty()) {
            return;
        }

        $transactionTypes = ['deposit', 'withdrawal', 'commission', 'payment', 'refund', 'transfer'];

        // Create transactions for completed orders
        foreach ($completedOrders as $order) {
            // Payment transaction
            Transaction::create([
                'member_id' => $order->member_id,
                'order_id' => $order->id,
                'type' => 'payment',
                'amount' => $order->total_amount,
                'balance_before' => rand(1000, 10000),
                'balance_after' => rand(500, 9000),
                'status' => 'completed',
                'reference_number' => 'TXN-' . strtoupper(uniqid()),
                'payment_method' => $order->payment_method,
                'description' => 'Payment for order ' . $order->order_number,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);

            // Commission transaction for sponsor if exists
            $member = Member::find($order->member_id);
            if ($member && $member->sponsor_id) {
                Transaction::create([
                    'member_id' => $member->sponsor_id,
                    'order_id' => $order->id,
                    'type' => 'commission',
                    'amount' => $order->commission_amount,
                    'balance_before' => rand(500, 5000),
                    'balance_after' => rand(600, 5500),
                    'status' => 'completed',
                    'reference_number' => 'COM-' . strtoupper(uniqid()),
                    'payment_method' => 'system',
                    'description' => 'Commission from downline order ' . $order->order_number,
                    'created_at' => $order->updated_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        }

        // Create general transactions
        foreach ($members as $member) {
            $numTransactions = rand(2, 8);
            
            for ($i = 0; $i < $numTransactions; $i++) {
                $type = $transactionTypes[array_rand($transactionTypes)];
                $amount = rand(100, 5000);
                $createdAt = Carbon::now()->subDays(rand(1, 60));
                
                Transaction::create([
                    'member_id' => $member->id,
                    'order_id' => null,
                    'type' => $type,
                    'amount' => $amount,
                    'balance_before' => rand(1000, 10000),
                    'balance_after' => $type === 'withdrawal' ? rand(500, 9000) : rand(1500, 11000),
                    'status' => rand(0, 10) > 1 ? 'completed' : 'pending',
                    'reference_number' => strtoupper($type[0]) . 'XN-' . strtoupper(uniqid()),
                    'payment_method' => $type === 'transfer' ? 'wallet' : 'mpesa',
                    'description' => ucfirst($type) . ' transaction',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}