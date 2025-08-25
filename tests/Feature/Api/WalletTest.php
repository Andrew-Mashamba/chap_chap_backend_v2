<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected $member;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // Create authenticated member
        $this->member = Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP001',
            'referral_code' => 'REF001',
            'wallet_balance' => 500000
        ]);

        $this->token = $this->member->createToken('auth-token')->plainTextToken;

        // Create wallet for member
        Wallet::create([
            'member_id' => $this->member->id,
            'balance' => 500000,
            'total_earned' => 750000,
            'total_withdrawn' => 250000
        ]);
    }

    public function test_get_wallet_balance()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/wallet/balance');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'balance' => 500000,
                    'total_earned' => 750000,
                    'total_withdrawn' => 250000,
                    'currency' => 'TZS'
                ]
            ]);
    }

    public function test_get_wallet_transactions()
    {
        // Create transactions
        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'commission',
            'amount' => 50000,
            'description' => 'Commission from order #001',
            'status' => 'completed',
            'reference' => 'COM001'
        ]);

        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'withdrawal',
            'amount' => -100000,
            'description' => 'Withdrawal to mobile money',
            'status' => 'completed',
            'reference' => 'WITH001'
        ]);

        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'bonus',
            'amount' => 25000,
            'description' => 'Level achievement bonus',
            'status' => 'completed',
            'reference' => 'BON001'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'amount',
                        'description',
                        'status',
                        'reference',
                        'created_at'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_get_wallet_transactions_with_filter()
    {
        // Create different types of transactions
        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'commission',
            'amount' => 50000,
            'description' => 'Commission 1',
            'status' => 'completed'
        ]);

        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'commission',
            'amount' => 30000,
            'description' => 'Commission 2',
            'status' => 'completed'
        ]);

        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'withdrawal',
            'amount' => -100000,
            'description' => 'Withdrawal',
            'status' => 'completed'
        ]);

        // Filter by type
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/wallet/transactions?type=commission');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Filter by date range
        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/wallet/transactions?start_date={$startDate}&end_date={$endDate}");

        $response2->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_request_withdrawal()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/withdraw', [
            'amount' => 100000,
            'method' => 'mobile_money',
            'account_number' => '255700000001',
            'account_name' => 'Test User'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Withdrawal request submitted successfully'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'transaction' => [
                    'id',
                    'amount',
                    'type',
                    'status',
                    'reference',
                    'created_at'
                ]
            ]);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'member_id' => $this->member->id,
            'type' => 'withdrawal',
            'amount' => -100000,
            'status' => 'pending'
        ]);
    }

    public function test_request_withdrawal_insufficient_balance()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/withdraw', [
            'amount' => 600000, // Balance is 500000
            'method' => 'mobile_money',
            'account_number' => '255700000001',
            'account_name' => 'Test User'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Insufficient balance'
            ]);
    }

    public function test_request_withdrawal_below_minimum()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/withdraw', [
            'amount' => 5000, // Assuming minimum is 10000
            'method' => 'mobile_money',
            'account_number' => '255700000001',
            'account_name' => 'Test User'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Minimum withdrawal amount is 10,000 TZS'
            ]);
    }

    public function test_get_commission_summary()
    {
        // Create commission transactions
        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'commission',
            'amount' => 50000,
            'description' => 'Direct sale commission',
            'status' => 'completed',
            'metadata' => json_encode(['level' => 1, 'order_id' => 1])
        ]);

        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'commission',
            'amount' => 30000,
            'description' => 'Team commission',
            'status' => 'completed',
            'metadata' => json_encode(['level' => 2, 'order_id' => 2])
        ]);

        Transaction::create([
            'member_id' => $this->member->id,
            'type' => 'commission',
            'amount' => 20000,
            'description' => 'Team commission',
            'status' => 'completed',
            'metadata' => json_encode(['level' => 3, 'order_id' => 3])
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/wallet/commission-summary');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_commission' => 100000,
                    'commission_by_level' => [
                        '1' => 50000,
                        '2' => 30000,
                        '3' => 20000
                    ],
                    'this_month' => 100000,
                    'last_month' => 0,
                    'average_monthly' => 100000
                ]
            ]);
    }

    public function test_transfer_to_member()
    {
        // Create recipient
        $recipient = Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Recipient User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP002',
            'referral_code' => 'REF002',
            'wallet_balance' => 0
        ]);

        Wallet::create([
            'member_id' => $recipient->id,
            'balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/transfer', [
            'recipient_phone' => '255700000002',
            'amount' => 50000,
            'pin' => '1234',
            'note' => 'Gift transfer'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Transfer completed successfully'
            ]);

        // Verify sender balance reduced
        $this->assertDatabaseHas('wallets', [
            'member_id' => $this->member->id,
            'balance' => 450000
        ]);

        // Verify recipient balance increased
        $this->assertDatabaseHas('wallets', [
            'member_id' => $recipient->id,
            'balance' => 50000
        ]);

        // Verify transactions created
        $this->assertDatabaseHas('transactions', [
            'member_id' => $this->member->id,
            'type' => 'transfer_out',
            'amount' => -50000
        ]);

        $this->assertDatabaseHas('transactions', [
            'member_id' => $recipient->id,
            'type' => 'transfer_in',
            'amount' => 50000
        ]);
    }

    public function test_transfer_with_wrong_pin()
    {
        $recipient = Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Recipient User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP002',
            'referral_code' => 'REF002'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/transfer', [
            'recipient_phone' => '255700000002',
            'amount' => 50000,
            'pin' => 'wrong_pin',
            'note' => 'Gift transfer'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid PIN'
            ]);
    }

    public function test_wallet_requires_authentication()
    {
        $response = $this->getJson('/api/wallet/balance');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}