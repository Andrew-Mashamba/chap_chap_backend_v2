<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('💰 Getting wallet balance', ['user_id' => $user->id]);

            $balance = $user->commission_balance ?? 0;

            return response()->json([
                'status' => 'success',
                'balance' => $balance,
                'currency' => 'TZS'
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting wallet balance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get wallet balance'
            ], 500);
        }
    }

    public function pay(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:100',
                'order_id' => 'required|string',
                'description' => 'required|string'
            ]);

            $user = $request->user();
            $amount = (float) $request->amount;
            $currentBalance = $user->commission_balance ?? 0;

            Log::channel('api')->info('💸 Processing wallet payment', [
                'user_id' => $user->id,
                'amount' => $amount,
                'order_id' => $request->order_id,
                'current_balance' => $currentBalance
            ]);

            // Check if user has sufficient balance
            if ($currentBalance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance'
                ], 422);
            }

            // Deduct amount from wallet
            $user->decrement('commission_balance', $amount);

            // Generate transaction ID
            $transactionId = 'TXN_' . Str::random(10);

            // In production, you'd create a transaction record here
            Log::channel('api')->info('✅ Wallet payment successful', [
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'order_id' => $request->order_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment successful',
                'transaction_id' => $transactionId,
                'remaining_balance' => $currentBalance - $amount
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error processing wallet payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment'
            ], 500);
        }
    }

    public function addFunds(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1000',
                'payment_method' => 'required|string'
            ]);

            $user = $request->user();
            $amount = (float) $request->amount;

            Log::channel('api')->info('💳 Adding funds to wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $request->payment_method
            ]);

            // In production, you'd integrate with actual payment providers
            // For now, we'll simulate successful payment processing
            
            // Add funds to wallet
            $user->increment('commission_balance', $amount);

            // Generate transaction ID
            $transactionId = 'TOP_' . Str::random(10);

            Log::channel('api')->info('✅ Funds added successfully', [
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'new_balance' => $user->commission_balance
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Funds added successfully',
                'transaction_id' => $transactionId,
                'new_balance' => $user->commission_balance
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error adding funds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add funds'
            ], 500);
        }
    }

    public function transactions(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📊 Getting transaction history', ['user_id' => $user->id]);

            // Mock transaction history - in production this would come from a transactions table
            $transactions = [
                [
                    'id' => 'TXN_001',
                    'type' => 'payment',
                    'amount' => -2500,
                    'description' => 'Payment for Order #ORD001',
                    'date' => now()->subDays(1)->toISOString(),
                    'status' => 'completed'
                ],
                [
                    'id' => 'TOP_001',
                    'type' => 'topup',
                    'amount' => 10000,
                    'description' => 'Wallet top-up via M-Pesa',
                    'date' => now()->subDays(3)->toISOString(),
                    'status' => 'completed'
                ],
                [
                    'id' => 'COM_001',
                    'type' => 'commission',
                    'amount' => 1500,
                    'description' => 'Commission from team sales',
                    'date' => now()->subDays(5)->toISOString(),
                    'status' => 'completed'
                ]
            ];

            return response()->json([
                'status' => 'success',
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting transaction history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get transaction history'
            ], 500);
        }
    }

    public function transfer(Request $request)
    {
        try {
            $request->validate([
                'recipient_phone' => 'required|string',
                'amount' => 'required|numeric|min:100',
                'description' => 'nullable|string'
            ]);

            $user = $request->user();
            $amount = (float) $request->amount;
            $currentBalance = $user->commission_balance ?? 0;

            Log::channel('api')->info('🔄 Processing wallet transfer', [
                'user_id' => $user->id,
                'amount' => $amount,
                'recipient' => $request->recipient_phone,
                'current_balance' => $currentBalance
            ]);

            // Check if user has sufficient balance
            if ($currentBalance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance'
                ], 422);
            }

            // Find recipient
            $recipient = Member::where('phone_number', $request->recipient_phone)->first();
            if (!$recipient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Recipient not found'
                ], 404);
            }

            // Process transfer
            $user->decrement('commission_balance', $amount);
            $recipient->increment('commission_balance', $amount);

            $transactionId = 'TRF_' . Str::random(10);

            Log::channel('api')->info('✅ Wallet transfer successful', [
                'user_id' => $user->id,
                'recipient_id' => $recipient->id,
                'transaction_id' => $transactionId,
                'amount' => $amount
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer successful',
                'transaction_id' => $transactionId,
                'remaining_balance' => $currentBalance - $amount
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error processing wallet transfer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process transfer'
            ], 500);
        }
    }

    public function withdraw(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1000',
                'payment_method' => 'required|string',
                'account_details' => 'required|array'
            ]);

            $user = $request->user();
            $amount = (float) $request->amount;
            $currentBalance = $user->commission_balance ?? 0;

            Log::channel('api')->info('💸 Processing withdrawal', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'current_balance' => $currentBalance
            ]);

            // Check if user has sufficient balance
            if ($currentBalance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance'
                ], 422);
            }

            // Process withdrawal (in production, integrate with payment providers)
            $user->decrement('commission_balance', $amount);

            $transactionId = 'WTH_' . Str::random(10);

            Log::channel('api')->info('✅ Withdrawal request submitted', [
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'amount' => $amount
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal request submitted successfully',
                'transaction_id' => $transactionId,
                'remaining_balance' => $currentBalance - $amount
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error processing withdrawal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process withdrawal'
            ], 500);
        }
    }
}
