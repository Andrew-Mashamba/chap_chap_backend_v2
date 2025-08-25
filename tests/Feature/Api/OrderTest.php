<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $member;
    protected $token;
    protected $product1;
    protected $product2;

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

        // Create test products
        $this->product1 = Product::create([
            'name' => 'Product 1',
            'description' => 'Description 1',
            'price' => 50000,
            'category' => 'Electronics',
            'stock_quantity' => 10,
            'commission_rate' => 10
        ]);

        $this->product2 = Product::create([
            'name' => 'Product 2',
            'description' => 'Description 2',
            'price' => 25000,
            'category' => 'Fashion',
            'stock_quantity' => 20,
            'commission_rate' => 15
        ]);
    }

    public function test_create_order_with_single_product()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2
                ]
            ],
            'delivery_address' => 'Dar es Salaam, Tanzania',
            'delivery_phone' => '255700000001'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order created successfully'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'order' => [
                    'id',
                    'order_number',
                    'member_id',
                    'total_amount',
                    'commission_amount',
                    'status',
                    'delivery_address',
                    'delivery_phone',
                    'created_at'
                ]
            ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'member_id' => $this->member->id,
            'total_amount' => 100000, // 50000 * 2
            'commission_amount' => 10000 // 10% of 100000
        ]);
    }

    public function test_create_order_with_multiple_products()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1
                ],
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 3
                ]
            ],
            'delivery_address' => 'Arusha, Tanzania',
            'delivery_phone' => '255700000002'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order created successfully'
            ]);

        // Calculate expected totals
        $product1Total = 50000 * 1;
        $product2Total = 25000 * 3;
        $totalAmount = $product1Total + $product2Total; // 125000
        $commission1 = $product1Total * 0.10; // 5000
        $commission2 = $product2Total * 0.15; // 11250
        $totalCommission = $commission1 + $commission2; // 16250

        $this->assertDatabaseHas('orders', [
            'member_id' => $this->member->id,
            'total_amount' => $totalAmount,
            'commission_amount' => $totalCommission
        ]);
    }

    public function test_create_order_with_insufficient_stock()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 15 // Stock is only 10
                ]
            ],
            'delivery_address' => 'Mwanza, Tanzania',
            'delivery_phone' => '255700000003'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Insufficient stock for Product 1'
            ]);
    }

    public function test_create_order_with_invalid_product()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders', [
            'products' => [
                [
                    'product_id' => 99999,
                    'quantity' => 1
                ]
            ],
            'delivery_address' => 'Dodoma, Tanzania',
            'delivery_phone' => '255700000004'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Product not found'
            ]);
    }

    public function test_get_member_orders()
    {
        // Create some orders
        Order::create([
            'member_id' => $this->member->id,
            'order_number' => 'ORD001',
            'total_amount' => 100000,
            'commission_amount' => 10000,
            'status' => 'pending',
            'delivery_address' => 'Dar es Salaam',
            'delivery_phone' => '255700000001'
        ]);

        Order::create([
            'member_id' => $this->member->id,
            'order_number' => 'ORD002',
            'total_amount' => 50000,
            'commission_amount' => 5000,
            'status' => 'completed',
            'delivery_address' => 'Arusha',
            'delivery_phone' => '255700000002'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'total_amount',
                        'commission_amount',
                        'status',
                        'delivery_address',
                        'delivery_phone',
                        'created_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_get_single_order()
    {
        $order = Order::create([
            'member_id' => $this->member->id,
            'order_number' => 'ORD001',
            'total_amount' => 100000,
            'commission_amount' => 10000,
            'status' => 'pending',
            'delivery_address' => 'Dar es Salaam',
            'delivery_phone' => '255700000001'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $order->id,
                    'order_number' => 'ORD001',
                    'total_amount' => 100000,
                    'status' => 'pending'
                ]
            ]);
    }

    public function test_get_order_not_belonging_to_member()
    {
        // Create another member
        $otherMember = Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Other User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP002',
            'referral_code' => 'REF002'
        ]);

        // Create order for other member
        $order = Order::create([
            'member_id' => $otherMember->id,
            'order_number' => 'ORD001',
            'total_amount' => 100000,
            'commission_amount' => 10000,
            'status' => 'pending',
            'delivery_address' => 'Dar es Salaam',
            'delivery_phone' => '255700000002'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/orders/' . $order->id);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized access to this order'
            ]);
    }

    public function test_cancel_order()
    {
        $order = Order::create([
            'member_id' => $this->member->id,
            'order_number' => 'ORD001',
            'total_amount' => 100000,
            'commission_amount' => 10000,
            'status' => 'pending',
            'delivery_address' => 'Dar es Salaam',
            'delivery_phone' => '255700000001'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/orders/' . $order->id . '/cancel');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order cancelled successfully'
            ]);

        // Verify order status was updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_cannot_cancel_completed_order()
    {
        $order = Order::create([
            'member_id' => $this->member->id,
            'order_number' => 'ORD001',
            'total_amount' => 100000,
            'commission_amount' => 10000,
            'status' => 'completed',
            'delivery_address' => 'Dar es Salaam',
            'delivery_phone' => '255700000001'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/orders/' . $order->id . '/cancel');

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot cancel a completed order'
            ]);
    }

    public function test_orders_require_authentication()
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}