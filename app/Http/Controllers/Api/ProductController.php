<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
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
    }

    public function show($id)
    {
        try {
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
            
            return response()->json([
                'status' => 'success',
                'data' => $this->transformProductForFlutter($product, $categoryId)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
    }

    public function groupedByCategory()
    {
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

        return response()->json([
            'status' => 'success',
            'data' => $grouped
        ]);
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
