<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::channel('api')->info('📦 Listing products', [
                'user_id' => $request->user()?->id,
                'category' => $request->category,
                'search' => $request->search,
                'limit' => $request->input('limit', 20),
                'ip' => $request->ip()
            ]);

            $query = Product::query();

            // Apply filters
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('merchant_name', 'like', "%{$search}%");
                });
            }

            // Paginate results
            $products = $query->paginate($request->input('limit', 20));

            Log::channel('api')->info('✅ Products listed successfully', [
                'total' => $products->total(),
                'page' => $products->currentPage()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error listing products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            Log::channel('api')->info('📦 Getting product details', [
                'product_id' => $id,
                'ip' => request()->ip()
            ]);

            $product = Product::findOrFail($id);

            // Map category to ID
            $categoryIdMap = [
                'Electronics' => 1,
                'Fashion' => 2,
                'Beauty & Health' => 3,
                'Home & Living' => 4,
                'Groceries' => 5,
                'Sports & Outdoors' => 6,
                'Baby & Kids' => 7,
            ];

            $categoryId = $categoryIdMap[$product->category] ?? 99;

            Log::channel('api')->info('✅ Product retrieved successfully', [
                'product_id' => $id,
                'product_name' => $product->name,
                'category' => $product->category
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $this->transformProductForFlutter($product, $categoryId)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('api')->warning('⚠️ Product not found', [
                'product_id' => $id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch product'
            ], 500);
        }
    }

    public function groupedByCategory()
    {
        try {
            Log::channel('api')->info('📦 Getting products grouped by category', [
                'ip' => request()->ip()
            ]);

            $products = Product::all()->groupBy('category');

            $grouped = [];
            $categoryIdMap = [
                'Electronics' => 1,
                'Fashion' => 2,
                'Beauty & Health' => 3,
                'Home & Living' => 4,
                'Groceries' => 5,
                'Sports & Outdoors' => 6,
                'Baby & Kids' => 7,
            ];

            foreach ($products as $category => $items) {
                $transformedItems = $items->map(function ($product) use ($category, $categoryIdMap) {
                    return $this->transformProductForFlutter($product, $categoryIdMap[$category] ?? 99);
                });
                $grouped[$category] = $transformedItems;
            }

            Log::channel('api')->info('✅ Products grouped successfully', [
                'categories_count' => count($grouped),
                'total_products' => $products->flatten()->count()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $grouped
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error grouping products by category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch grouped products'
            ], 500);
        }
    }

    private function transformProductForFlutter($product, $categoryId = 1)
    {
        // Get first image from media array or use placeholder
        $imageUrl = 'https://via.placeholder.com/300x300?text=' . urlencode($product->name);
        if (!empty($product->media) && is_array($product->media)) {
            $imageUrl = $product->media[0];
        } elseif (!empty($product->media_json) && is_array($product->media_json)) {
            $imageUrl = $product->media_json[0];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->selling_price,
            'image_url' => $imageUrl,
            'category_id' => $categoryId,
            'popularity_score' => rand(50, 100),
            'monthly_views' => rand(100, 1000),
            'monthly_sales' => rand(10, 100),
            'monthly_revenue' => (float) $product->selling_price * rand(10, 100),
            'last_viewed_at' => now()->subDays(rand(0, 7))->toIso8601String(),
            'last_sold_at' => now()->subDays(rand(0, 30))->toIso8601String(),
            // Include original fields for compatibility
            'selling_price' => $product->selling_price,
            'original_price' => $product->original_price,
            'discount_price' => $product->discount_price,
            'merchant_name' => $product->merchant_name,
            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'brand' => $product->brand,
            'sku' => $product->sku,
            'stock_quantity' => $product->stock_quantity,
            'is_featured' => $product->is_featured,
            'is_active' => $product->is_active,
        ];
    }
}
