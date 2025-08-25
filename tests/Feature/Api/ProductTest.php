<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductTest extends TestCase
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
            'referral_code' => 'REF001'
        ]);

        $this->token = $this->member->createToken('auth-token')->plainTextToken;
    }

    public function test_get_products_list()
    {
        // Create some products
        Product::create([
            'name' => 'Product 1',
            'description' => 'Description 1',
            'selling_price' => 50000,
            'category' => 'Electronics',
            'total_item_available' => 10,
            'media_json' => ['image' => 'https://example.com/product1.jpg']
        ]);

        Product::create([
            'name' => 'Product 2',
            'description' => 'Description 2',
            'selling_price' => 25000,
            'category' => 'Fashion',
            'total_item_available' => 20,
            'media_json' => ['image' => 'https://example.com/product2.jpg']
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'category',
                        'stock_quantity',
                        'image_url'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_get_products_list_with_search()
    {
        // Create products
        Product::create([
            'name' => 'iPhone 15',
            'description' => 'Latest iPhone',
            'price' => 2500000,
            'category' => 'Electronics',
            'stock_quantity' => 5
        ]);

        Product::create([
            'name' => 'Samsung Galaxy',
            'description' => 'Android phone',
            'price' => 1500000,
            'category' => 'Electronics',
            'stock_quantity' => 8
        ]);

        Product::create([
            'name' => 'Nike Shoes',
            'description' => 'Running shoes',
            'price' => 150000,
            'category' => 'Fashion',
            'stock_quantity' => 15
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products?search=phone');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_products_list_with_category_filter()
    {
        // Create products in different categories
        Product::create([
            'name' => 'Laptop',
            'price' => 3000000,
            'category' => 'Electronics',
            'stock_quantity' => 3
        ]);

        Product::create([
            'name' => 'T-Shirt',
            'price' => 50000,
            'category' => 'Fashion',
            'stock_quantity' => 30
        ]);

        Product::create([
            'name' => 'Phone',
            'price' => 1000000,
            'category' => 'Electronics',
            'stock_quantity' => 10
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products?category=Electronics');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_single_product()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100000,
            'category' => 'Electronics',
            'stock_quantity' => 5,
            'image_url' => 'https://example.com/test.jpg'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Test Product',
                    'price' => 100000,
                    'category' => 'Electronics'
                ]
            ]);
    }

    public function test_get_non_existent_product()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products/999999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Product not found'
            ]);
    }

    public function test_get_products_grouped_by_category()
    {
        // Create products in different categories
        Product::create([
            'name' => 'Laptop',
            'price' => 3000000,
            'category' => 'Electronics',
            'stock_quantity' => 3
        ]);

        Product::create([
            'name' => 'Phone',
            'price' => 1000000,
            'category' => 'Electronics',
            'stock_quantity' => 10
        ]);

        Product::create([
            'name' => 'T-Shirt',
            'price' => 50000,
            'category' => 'Fashion',
            'stock_quantity' => 30
        ]);

        Product::create([
            'name' => 'Jeans',
            'price' => 80000,
            'category' => 'Fashion',
            'stock_quantity' => 20
        ]);

        Product::create([
            'name' => 'Rice',
            'price' => 25000,
            'category' => 'Food',
            'stock_quantity' => 100
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/products/grouped-by-category');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'Electronics',
                    'Fashion',
                    'Food'
                ]
            ]);

        // Check that each category has the correct number of products
        $data = $response->json('data');
        $this->assertCount(2, $data['Electronics']);
        $this->assertCount(2, $data['Fashion']);
        $this->assertCount(1, $data['Food']);
    }

    public function test_products_require_authentication()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}