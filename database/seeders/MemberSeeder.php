<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberSeeder extends Seeder
{
    public function run()
    {
        // Create admin/test user
        $admin = Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'full_name' => 'Admin User',
            'phone_number' => '0700000000',
            'pin' => Hash::make('1234'),
            'email' => 'admin@chapchap.com',
            'shop_name' => 'ChapChap HQ',
            'region' => 'Nairobi',
            'district' => 'Nairobi CBD',
            'account_status' => 'active',
            'seller_level' => 4, // platinum level
            'total_sales_volume' => 50000.00,
            'commission_balance' => 7500.00,
            'referral_code' => 'ADMIN001',
            'is_team_leader' => true,
            'kyc_verified' => true,
        ]);

        // Create team leaders with MLM structure
        $leader1 = Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'John',
            'last_name' => 'Kamau',
            'full_name' => 'John Kamau',
            'phone_number' => '0711111111',
            'pin' => Hash::make('1234'),
            'email' => 'john.kamau@example.com',
            'shop_name' => 'Kamau Electronics',
            'region' => 'Nairobi',
            'district' => 'Westlands',
            'account_status' => 'active',
            'seller_level' => 3, // gold level
            'total_sales_volume' => 25000.00,
            'commission_balance' => 3000.00,
            'upline_id' => $admin->id,
            'referral_code' => 'JK2024',
            'is_team_leader' => true,
            'kyc_verified' => true,
        ]);

        $leader2 = Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'Mary',
            'last_name' => 'Wanjiru',
            'full_name' => 'Mary Wanjiru',
            'phone_number' => '0722222222',
            'pin' => Hash::make('1234'),
            'email' => 'mary.wanjiru@example.com',
            'shop_name' => 'Wanjiru Fashion House',
            'region' => 'Nairobi',
            'district' => 'Kilimani',
            'account_status' => 'active',
            'seller_level' => 3, // gold level
            'total_sales_volume' => 20000.00,
            'commission_balance' => 2400.00,
            'upline_id' => $admin->id,
            'referral_code' => 'MW2024',
            'is_team_leader' => true,
            'kyc_verified' => true,
        ]);

        // Create silver level members (downline of leaders)
        $member1 = Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'Peter',
            'last_name' => 'Ochieng',
            'full_name' => 'Peter Ochieng',
            'phone_number' => '0733333333',
            'pin' => Hash::make('1234'),
            'email' => 'peter.ochieng@example.com',
            'shop_name' => 'Ochieng Supplies',
            'region' => 'Kisumu',
            'district' => 'Kisumu Central',
            'account_status' => 'active',
            'seller_level' => 2, // silver level
            'total_sales_volume' => 10000.00,
            'commission_balance' => 800.00,
            'upline_id' => $leader1->id,
            'referred_by_code' => 'JK2024',
            'referral_code' => 'PO2024',
            'kyc_verified' => true,
        ]);

        $member2 = Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'Grace',
            'last_name' => 'Muthoni',
            'full_name' => 'Grace Muthoni',
            'phone_number' => '0744444444',
            'pin' => Hash::make('1234'),
            'email' => 'grace.muthoni@example.com',
            'shop_name' => 'Grace Beauty Shop',
            'region' => 'Kiambu',
            'district' => 'Thika Town',
            'account_status' => 'active',
            'seller_level' => 2, // silver level
            'total_sales_volume' => 8000.00,
            'commission_balance' => 640.00,
            'upline_id' => $leader1->id,
            'referred_by_code' => 'JK2024',
            'referral_code' => 'GM2024',
            'kyc_verified' => true,
        ]);

        // Create bronze level members (new recruits)
        Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'James',
            'last_name' => 'Mwangi',
            'full_name' => 'James Mwangi',
            'phone_number' => '0755555555',
            'pin' => Hash::make('1234'),
            'email' => 'james.mwangi@example.com',
            'shop_name' => 'Mwangi Store',
            'region' => 'Nakuru',
            'district' => 'Nakuru Town',
            'account_status' => 'active',
            'seller_level' => 1, // bronze level
            'total_sales_volume' => 2000.00,
            'commission_balance' => 100.00,
            'upline_id' => $member1->id,
            'referred_by_code' => 'PO2024',
            'referral_code' => 'JM2024',
        ]);

        Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'Lucy',
            'last_name' => 'Njeri',
            'full_name' => 'Lucy Njeri',
            'phone_number' => '0766666666',
            'pin' => Hash::make('1234'),
            'email' => 'lucy.njeri@example.com',
            'shop_name' => 'Njeri Boutique',
            'region' => 'Nairobi',
            'district' => 'Mombasa Road',
            'account_status' => 'active',
            'seller_level' => 1, // bronze level
            'total_sales_volume' => 1500.00,
            'commission_balance' => 75.00,
            'upline_id' => $member2->id,
            'referred_by_code' => 'GM2024',
            'referral_code' => 'LN2024',
        ]);

        Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'David',
            'last_name' => 'Kiprono',
            'full_name' => 'David Kiprono',
            'phone_number' => '0777777777',
            'pin' => Hash::make('1234'),
            'email' => 'david.kiprono@example.com',
            'shop_name' => 'Kiprono General Store',
            'region' => 'Uasin Gishu',
            'district' => 'Eldoret Town',
            'account_status' => 'active',
            'seller_level' => 1, // bronze level
            'total_sales_volume' => 1000.00,
            'commission_balance' => 50.00,
            'upline_id' => $leader2->id,
            'referred_by_code' => 'MW2024',
            'referral_code' => 'DK2024',
        ]);

        // Create inactive member for testing
        Member::create([
            'uuid' => Str::uuid(),
            'first_name' => 'Test',
            'last_name' => 'Inactive',
            'full_name' => 'Test Inactive',
            'phone_number' => '0788888888',
            'pin' => Hash::make('1234'),
            'email' => 'inactive@example.com',
            'shop_name' => 'Inactive Shop',
            'region' => 'Unknown',
            'district' => 'Unknown',
            'account_status' => 'suspended',
            'seller_level' => 1, // bronze level
            'total_sales_volume' => 0.00,
            'commission_balance' => 0.00,
            'upline_id' => $leader2->id,
            'referral_code' => 'INACT01',
        ]);

        // Update downline counts for uplines
        Member::where('id', $admin->id)->update(['total_downlines' => 2]);
        Member::where('id', $leader1->id)->update(['total_downlines' => 2]);
        Member::where('id', $leader2->id)->update(['total_downlines' => 2]);
        Member::where('id', $member1->id)->update(['total_downlines' => 1]);
        Member::where('id', $member2->id)->update(['total_downlines' => 1]);
    }
}