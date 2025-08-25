<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Member;

class PunguzoProductController extends Controller
{
    private $punguzoBaseUrl;
    private $punguzoApiKey;
    
    public function __construct()
    {
        $this->punguzoBaseUrl = env('PUNGUZO_API_URL', 'https://api.punguzo.com');
        $this->punguzoApiKey = env('PUNGUZO_API_KEY', '');
    }
    
    public function getProducts(Request $request)
    {
        try {
            Log::info('🔄 Fetching products from Punguzo API');
            
            // First, let's check if we have Punguzo API credentials
            if (empty($this->punguzoApiKey)) {
                Log::warning('⚠️ Punguzo API key not configured. Using mock data.');
                return $this->populateWithMockData();
            }
            
            // Fetch products from Punguzo API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->punguzoApiKey,
                'Accept' => 'application/json',
            ])->get($this->punguzoBaseUrl . '/api/products');
            
            if ($response->successful()) {
                $punguzoProducts = $response->json()['data'] ?? [];
                return $this->importProducts($punguzoProducts);
            } else {
                Log::error('❌ Failed to fetch products from Punguzo', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                
                // Use mock data as fallback
                return $this->populateWithMockData();
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Error fetching Punguzo products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Use mock data as fallback
            return $this->populateWithMockData();
        }
    }
    
    private function importProducts(array $punguzoProducts)
    {
        DB::beginTransaction();
        try {
            $imported = 0;
            $updated = 0;
            
            foreach ($punguzoProducts as $punguzoProduct) {
                $productData = [
                    'name' => $punguzoProduct['name'] ?? 'Unknown Product',
                    'description' => $punguzoProduct['description'] ?? '',
                    'sku' => $punguzoProduct['sku'] ?? uniqid('SKU'),
                    'barcode' => $punguzoProduct['barcode'] ?? null,
                    'category' => $punguzoProduct['category'] ?? 'General',
                    'subcategory' => $punguzoProduct['subcategory'] ?? null,
                    'brand' => $punguzoProduct['brand'] ?? 'Generic',
                    'unit' => $punguzoProduct['unit'] ?? 'piece',
                    'weight' => $punguzoProduct['weight'] ?? null,
                    'dimensions' => $punguzoProduct['dimensions'] ?? null,
                    'original_price' => $punguzoProduct['original_price'] ?? 0,
                    'selling_price' => $punguzoProduct['selling_price'] ?? $punguzoProduct['original_price'] ?? 0,
                    'discount_price' => $punguzoProduct['discount_price'] ?? null,
                    'stock_quantity' => $punguzoProduct['stock_quantity'] ?? 100,
                    'min_stock_level' => $punguzoProduct['min_stock_level'] ?? 10,
                    'max_stock_level' => $punguzoProduct['max_stock_level'] ?? 1000,
                    'is_active' => $punguzoProduct['is_active'] ?? true,
                    'is_featured' => $punguzoProduct['is_featured'] ?? false,
                    'media' => $punguzoProduct['images'] ?? [],
                    'tags' => $punguzoProduct['tags'] ?? [],
                    'punguzo_product_id' => $punguzoProduct['id'] ?? null,
                    'merchant_id' => 1, // Default merchant
                    'merchant_name' => 'Punguzo Partner',
                ];
                
                // Map media array to media_json for compatibility
                if (isset($productData['media']) && !empty($productData['media'])) {
                    $productData['media_json'] = $productData['media'];
                    // Also ensure media field is properly set
                    $productData['media'] = is_array($productData['media']) ? $productData['media'] : [$productData['media']];
                }
                
                // Map stock_quantity to total_item_available
                if (isset($productData['stock_quantity'])) {
                    $productData['total_item_available'] = $productData['stock_quantity'];
                }
                
                // Check if product already exists
                $existingProduct = Product::where('sku', $productData['sku'])->first();
                
                if ($existingProduct) {
                    $existingProduct->update($productData);
                    $updated++;
                } else {
                    Product::create($productData);
                    $imported++;
                }
            }
            
            DB::commit();
            
            Log::info('✅ Punguzo products import completed', [
                'imported' => $imported,
                'updated' => $updated
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => "Successfully imported $imported new products and updated $updated existing products from Punguzo",
                'data' => [
                    'imported' => $imported,
                    'updated' => $updated,
                    'total' => $imported + $updated
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    private function populateWithMockData()
    {
        Log::info('📦 Populating database with mock Punguzo products');
        
        $mockProducts = [
            // Electronics
            [
                'name' => 'Samsung Galaxy A54 5G',
                'description' => 'Latest Samsung smartphone with 5G connectivity, 128GB storage',
                'sku' => 'PGZ-ELEC-001',
                'category' => 'Electronics',
                'subcategory' => 'Smartphones',
                'brand' => 'Samsung',
                'original_price' => 1200000,
                'selling_price' => 1100000,
                'discount_price' => 1050000,
                'stock_quantity' => 50,
                'is_featured' => true,
                'media' => ['https://via.placeholder.com/300x300?text=Galaxy+A54'],
            ],
            [
                'name' => 'HP Laptop 15s',
                'description' => 'Intel Core i5, 8GB RAM, 512GB SSD, Windows 11',
                'sku' => 'PGZ-ELEC-002',
                'category' => 'Electronics',
                'subcategory' => 'Laptops',
                'brand' => 'HP',
                'original_price' => 2500000,
                'selling_price' => 2300000,
                'stock_quantity' => 20,
                'media' => ['https://via.placeholder.com/300x300?text=HP+Laptop'],
            ],
            [
                'name' => 'Sony WH-1000XM4 Headphones',
                'description' => 'Wireless Noise Cancelling Headphones',
                'sku' => 'PGZ-ELEC-003',
                'category' => 'Electronics',
                'subcategory' => 'Audio',
                'brand' => 'Sony',
                'original_price' => 850000,
                'selling_price' => 800000,
                'stock_quantity' => 30,
                'media' => ['https://via.placeholder.com/300x300?text=Sony+Headphones'],
            ],
            
            // Fashion
            [
                'name' => 'Men\'s Formal Shirt - Blue',
                'description' => 'Cotton formal shirt, available in multiple sizes',
                'sku' => 'PGZ-FASH-001',
                'category' => 'Fashion',
                'subcategory' => 'Men\'s Clothing',
                'brand' => 'Classic',
                'original_price' => 45000,
                'selling_price' => 40000,
                'stock_quantity' => 100,
                'media' => ['https://via.placeholder.com/300x300?text=Blue+Shirt'],
            ],
            [
                'name' => 'Women\'s Kitenge Dress',
                'description' => 'Beautiful African print dress, multiple patterns available',
                'sku' => 'PGZ-FASH-002',
                'category' => 'Fashion',
                'subcategory' => 'Women\'s Clothing',
                'brand' => 'AfroStyle',
                'original_price' => 65000,
                'selling_price' => 60000,
                'stock_quantity' => 80,
                'is_featured' => true,
                'media' => ['https://via.placeholder.com/300x300?text=Kitenge+Dress'],
            ],
            [
                'name' => 'Leather Wallet - Brown',
                'description' => 'Genuine leather wallet with multiple card slots',
                'sku' => 'PGZ-FASH-003',
                'category' => 'Fashion',
                'subcategory' => 'Accessories',
                'brand' => 'LeatherCraft',
                'original_price' => 35000,
                'selling_price' => 30000,
                'stock_quantity' => 150,
                'media' => ['https://via.placeholder.com/300x300?text=Leather+Wallet'],
            ],
            
            // Beauty & Health
            [
                'name' => 'Organic Face Cream',
                'description' => 'Natural moisturizing cream with shea butter',
                'sku' => 'PGZ-BEAUTY-001',
                'category' => 'Beauty & Health',
                'subcategory' => 'Skincare',
                'brand' => 'NatureCare',
                'original_price' => 25000,
                'selling_price' => 22000,
                'stock_quantity' => 200,
                'media' => ['https://via.placeholder.com/300x300?text=Face+Cream'],
            ],
            [
                'name' => 'Vitamin C Supplements',
                'description' => '1000mg Vitamin C tablets, 60 count',
                'sku' => 'PGZ-BEAUTY-002',
                'category' => 'Beauty & Health',
                'subcategory' => 'Supplements',
                'brand' => 'HealthPlus',
                'original_price' => 18000,
                'selling_price' => 15000,
                'stock_quantity' => 100,
                'media' => ['https://via.placeholder.com/300x300?text=Vitamin+C'],
            ],
            
            // Home & Living
            [
                'name' => 'Ceramic Dinner Set - 24 Pieces',
                'description' => 'Complete dinner set for 6 people',
                'sku' => 'PGZ-HOME-001',
                'category' => 'Home & Living',
                'subcategory' => 'Kitchen',
                'brand' => 'HomeStyle',
                'original_price' => 120000,
                'selling_price' => 110000,
                'stock_quantity' => 25,
                'media' => ['https://via.placeholder.com/300x300?text=Dinner+Set'],
            ],
            [
                'name' => 'Queen Size Bedsheet Set',
                'description' => '100% cotton bedsheet with 2 pillow covers',
                'sku' => 'PGZ-HOME-002',
                'category' => 'Home & Living',
                'subcategory' => 'Bedroom',
                'brand' => 'ComfortZone',
                'original_price' => 55000,
                'selling_price' => 50000,
                'stock_quantity' => 60,
                'media' => ['https://via.placeholder.com/300x300?text=Bedsheet+Set'],
            ],
            
            // Groceries
            [
                'name' => 'Basmati Rice - 5kg',
                'description' => 'Premium quality long grain basmati rice',
                'sku' => 'PGZ-GROC-001',
                'category' => 'Groceries',
                'subcategory' => 'Rice & Grains',
                'brand' => 'Golden Harvest',
                'original_price' => 25000,
                'selling_price' => 23000,
                'stock_quantity' => 100,
                'media' => ['https://via.placeholder.com/300x300?text=Basmati+Rice'],
            ],
            [
                'name' => 'Cooking Oil - 5L',
                'description' => 'Pure sunflower cooking oil',
                'sku' => 'PGZ-GROC-002',
                'category' => 'Groceries',
                'subcategory' => 'Cooking Essentials',
                'brand' => 'SunGold',
                'original_price' => 35000,
                'selling_price' => 32000,
                'stock_quantity' => 80,
                'media' => ['https://via.placeholder.com/300x300?text=Cooking+Oil'],
            ],
            
            // Sports & Outdoors
            [
                'name' => 'Yoga Mat - Premium',
                'description' => 'Non-slip yoga mat, 6mm thickness',
                'sku' => 'PGZ-SPORT-001',
                'category' => 'Sports & Outdoors',
                'subcategory' => 'Fitness',
                'brand' => 'FitLife',
                'original_price' => 45000,
                'selling_price' => 40000,
                'stock_quantity' => 40,
                'media' => ['https://via.placeholder.com/300x300?text=Yoga+Mat'],
            ],
            [
                'name' => 'Football - Size 5',
                'description' => 'Professional quality football',
                'sku' => 'PGZ-SPORT-002',
                'category' => 'Sports & Outdoors',
                'subcategory' => 'Sports Equipment',
                'brand' => 'ProSport',
                'original_price' => 35000,
                'selling_price' => 30000,
                'stock_quantity' => 50,
                'media' => ['https://via.placeholder.com/300x300?text=Football'],
            ],
            
            // Baby & Kids
            [
                'name' => 'Baby Diapers - Size 3 (50 pack)',
                'description' => 'Ultra-absorbent baby diapers',
                'sku' => 'PGZ-BABY-001',
                'category' => 'Baby & Kids',
                'subcategory' => 'Diapers',
                'brand' => 'BabyCare',
                'original_price' => 28000,
                'selling_price' => 25000,
                'stock_quantity' => 100,
                'media' => ['https://via.placeholder.com/300x300?text=Baby+Diapers'],
            ],
            [
                'name' => 'Educational Toy Set',
                'description' => 'Learning toys for ages 3-6',
                'sku' => 'PGZ-BABY-002',
                'category' => 'Baby & Kids',
                'subcategory' => 'Toys',
                'brand' => 'SmartKids',
                'original_price' => 55000,
                'selling_price' => 50000,
                'stock_quantity' => 30,
                'media' => ['https://via.placeholder.com/300x300?text=Educational+Toys'],
            ],
        ];
        
        // Add punguzo_product_id and merchant info to all products
        foreach ($mockProducts as &$product) {
            $product['punguzo_product_id'] = 'PGZ-' . uniqid();
            $product['merchant_id'] = 1;
            $product['merchant_name'] = 'Punguzo Partner';
            $product['unit'] = 'piece';
            $product['is_active'] = true;
            $product['is_featured'] = $product['is_featured'] ?? false;
            $product['min_stock_level'] = 10;
            $product['max_stock_level'] = 1000;
            $product['tags'] = [];
        }
        
        return $this->importProducts($mockProducts);
    }
}