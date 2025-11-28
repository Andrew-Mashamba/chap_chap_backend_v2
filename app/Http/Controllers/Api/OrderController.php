<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📋 Listing orders', [
                'user_id' => $user->id,
                'status_filter' => $request->status,
                'limit' => $request->input('limit', 20)
            ]);

            $query = Order::where('member_id', $user->id);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $orders = $query->paginate($request->input('limit', 20));

            Log::channel('api')->info('✅ Orders listed successfully', [
                'user_id' => $user->id,
                'total' => $orders->total(),
                'page' => $orders->currentPage()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error listing orders', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch orders'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('🛒 Creating order', [
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'payment_method' => $request->payment_method,
                'delivery_method' => $request->delivery_method
            ]);

            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'delivery_address' => 'required|string',
                'payment_method' => 'required|string|in:mobile_money,bank_transfer,cash',
                'delivery_method' => 'required|string|in:pickup,delivery',
            ]);

            $product = Product::findOrFail($validated['product_id']);

            // Check if product is available
            if ($product->total_item_available < $validated['quantity']) {
                Log::channel('api')->warning('⚠️ Insufficient stock for order', [
                    'user_id' => $user->id,
                    'product_id' => $validated['product_id'],
                    'requested_quantity' => $validated['quantity'],
                    'available_stock' => $product->total_item_available
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock available'
                ], 400);
            }

            // Calculate total price
            $totalPrice = $product->selling_price * $validated['quantity'];

            // Add delivery fee if delivery method is selected
            if ($validated['delivery_method'] === 'delivery') {
                $totalPrice += $product->within_region_delivery_fee;
            }

            $order = Order::create([
                'member_id' => $user->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_price' => $totalPrice,
                'delivery_address' => $validated['delivery_address'],
                'payment_method' => $validated['payment_method'],
                'delivery_method' => $validated['delivery_method'],
                'status' => 'pending',
            ]);

            // Update product stock
            $product->decrement('total_item_available', $validated['quantity']);

            Log::channel('api')->info('✅ Order created successfully', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_price' => $totalPrice
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('api')->warning('⚠️ Order validation failed', [
                'user_id' => $request->user()?->id,
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error creating order', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = request()->user();
            Log::channel('api')->info('📦 Getting order details', [
                'user_id' => $user->id,
                'order_id' => $id
            ]);

            $order = Order::where('member_id', $user->id)->findOrFail($id);

            Log::channel('api')->info('✅ Order retrieved successfully', [
                'user_id' => $user->id,
                'order_id' => $id,
                'order_status' => $order->status
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('api')->warning('⚠️ Order not found', [
                'user_id' => request()->user()?->id,
                'order_id' => $id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting order', [
                'user_id' => request()->user()?->id,
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch order'
            ], 500);
        }
    }
}
