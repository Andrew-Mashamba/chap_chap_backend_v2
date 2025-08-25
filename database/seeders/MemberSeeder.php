<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    public function run()
    {
        // Create test user
        Member::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '0711111111',
            'pin' => Hash::make('1234'), // Hash the PIN using Bcrypt
            'email' => 'test@example.com',
            'shop_name' => 'Test Shop',
            'shop_location' => 'Test Location',
            'status' => 'active',
            'seller_level' => 'bronze',
            'commission_rate' => 5.00,
            'total_sales' => 0.00,
            'total_commission' => 0.00,
            'wallet_balance' => 0.00,
        ]);

        // Create additional test members
        Member::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '0722222222',
            'pin' => Hash::make('1234'),
            'email' => 'john@example.com',
            'shop_name' => 'John\'s Shop',
            'shop_location' => 'Location 1',
            'status' => 'active',
            'seller_level' => 'silver',
            'commission_rate' => 7.50,
            'total_sales' => 1000.00,
            'total_commission' => 75.00,
            'wallet_balance' => 75.00,
        ]);

        Member::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone_number' => '0733333333',
            'pin' => Hash::make('1234'),
            'email' => 'jane@example.com',
            'shop_name' => 'Jane\'s Shop',
            'shop_location' => 'Location 2',
            'status' => 'active',
            'seller_level' => 'gold',
            'commission_rate' => 10.00,
            'total_sales' => 5000.00,
            'total_commission' => 500.00,
            'wallet_balance' => 500.00,
        ]);
    }
}
