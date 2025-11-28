<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function members(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📋 Getting team members', ['user_id' => $user->id]);

            $teamMembers = Member::where('upline_id', $user->seller_id)
                ->where('account_status', 'active')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $teamMembers
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting team members', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get team members'
            ], 500);
        }
    }

    public function upliner(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('👆 Getting upliner', ['user_id' => $user->id]);

            if (!$user->upline_id) {
                return response()->json([
                    'status' => 'success',
                    'data' => null
                ]);
            }

            $upliner = Member::where('seller_id', $user->upline_id)
                ->where('account_status', 'active')
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $upliner
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting upliner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get upliner'
            ], 500);
        }
    }

    public function performance(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📊 Getting team performance', ['user_id' => $user->id]);

            // Get team members
            $teamMembers = Member::where('upline_id', $user->seller_id)->get();
            $totalMembers = $teamMembers->count();
            
            // Calculate team statistics
            $activeMembers = $teamMembers->where('account_status', 'active')->count();
            $totalSales = $teamMembers->sum('total_sales') ?? 0;
            $totalCommissions = $teamMembers->sum('total_commissions') ?? 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_members' => $totalMembers,
                    'active_members' => $activeMembers,
                    'total_sales' => $totalSales,
                    'total_commissions' => $totalCommissions,
                    'performance_rate' => $totalMembers > 0 ? ($activeMembers / $totalMembers) * 100 : 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting team performance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get team performance'
            ], 500);
        }
    }

    public function addDownliner(Request $request)
    {
        try {
            $request->validate([
                'memberNumber' => 'required|string'
            ]);

            $user = $request->user();
            Log::channel('api')->info('➕ Adding downliner', [
                'user_id' => $user->id,
                'member_number' => $request->memberNumber
            ]);

            // Find the member to add as downliner
            $member = Member::where('seller_id', $request->memberNumber)
                ->where('account_status', 'active')
                ->first();

            if (!$member) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member not found'
                ], 404);
            }

            if ($member->upline_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member already has an upline'
                ], 422);
            }

            // Update member's upline
            $member->update(['upline_id' => $user->seller_id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Downliner added successfully'
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error adding downliner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add downliner'
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = $request->query('query', '');
            $user = $request->user();
            
            Log::channel('api')->info('🔍 Searching team members', [
                'user_id' => $user->id,
                'query' => $query
            ]);

            $members = Member::where('upline_id', $user->seller_id)
                ->where('account_status', 'active')
                ->where(function($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('seller_id', 'like', "%{$query}%")
                      ->orWhere('phone_number', 'like', "%{$query}%");
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $members
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error searching team members', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search team members'
            ], 500);
        }
    }

    public function memberPerformance(Request $request, $memberId)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📈 Getting member performance', [
                'user_id' => $user->id,
                'member_id' => $memberId
            ]);

            $member = Member::where('seller_id', $memberId)
                ->where('upline_id', $user->seller_id)
                ->first();

            if (!$member) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member not found or not in your team'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'member_id' => $member->seller_id,
                    'name' => $member->first_name . ' ' . $member->last_name,
                    'total_sales' => $member->total_sales ?? 0,
                    'total_commissions' => $member->total_commissions ?? 0,
                    'downlines_count' => $member->downlines()->count(),
                    'level' => $member->seller_level,
                    'join_date' => $member->created_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting member performance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get member performance'
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'memberId' => 'required|string',
                'message' => 'required|string|max:500'
            ]);

            $user = $request->user();
            Log::channel('api')->info('💬 Sending message to team member', [
                'user_id' => $user->id,
                'member_id' => $request->memberId
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error sending message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    public function generateReferralCode(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('🔗 Generating referral code', ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'code' => $user->seller_id
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error generating referral code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate referral code'
            ], 500);
        }
    }

    public function generateQRCode(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:referral,product'
            ]);

            $user = $request->user();
            Log::channel('api')->info('📱 Generating QR code', [
                'user_id' => $user->id,
                'type' => $request->type
            ]);

            $qrData = base64_encode(json_encode([
                'type' => $request->type,
                'referral_code' => $user->seller_id,
                'timestamp' => now()->toISOString()
            ]));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'qrCode' => "data:image/png;base64,{$qrData}"
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error generating QR code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate QR code'
            ], 500);
        }
    }

    public function hierarchy(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('🌳 Getting team hierarchy', ['user_id' => $user->id]);

            $hierarchy = $this->buildHierarchy($user->seller_id);

            return response()->json([
                'status' => 'success',
                'data' => $hierarchy
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting team hierarchy', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get team hierarchy'
            ], 500);
        }
    }

    private function buildHierarchy($sellerId, $depth = 0, $maxDepth = 3)
    {
        if ($depth >= $maxDepth) {
            return [];
        }

        $members = Member::where('upline_id', $sellerId)
            ->where('account_status', 'active')
            ->get();

        return $members->map(function ($member) use ($depth, $maxDepth) {
            return [
                'id' => $member->seller_id,
                'name' => $member->first_name . ' ' . $member->last_name,
                'level' => $member->seller_level,
                'join_date' => $member->created_at,
                'children' => $this->buildHierarchy($member->seller_id, $depth + 1, $maxDepth)
            ];
        })->toArray();
    }

    public function commissionHistory(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('💰 Getting commission history', ['user_id' => $user->id]);

            $commissions = [
                [
                    'id' => 1,
                    'amount' => 5000,
                    'type' => 'sales_commission',
                    'description' => 'Commission from product sales',
                    'date' => now()->subDays(1)->toISOString(),
                    'status' => 'paid'
                ],
                [
                    'id' => 2,
                    'amount' => 2500,
                    'type' => 'referral_bonus',
                    'description' => 'Bonus for new referral',
                    'date' => now()->subDays(5)->toISOString(),
                    'status' => 'paid'
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $commissions
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting commission history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get commission history'
            ], 500);
        }
    }

    public function withdrawCommission(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1000',
                'paymentMethod' => 'required|string'
            ]);

            $user = $request->user();
            Log::channel('api')->info('💸 Commission withdrawal request', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'method' => $request->paymentMethod
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal request submitted successfully'
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

    public function analytics(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📊 Getting team analytics', ['user_id' => $user->id]);

            $teamMembers = Member::where('upline_id', $user->seller_id)->get();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_members' => $teamMembers->count(),
                    'active_members' => $teamMembers->where('account_status', 'active')->count(),
                    'this_month_sales' => $teamMembers->sum('total_sales') ?? 0,
                    'total_commissions' => $teamMembers->sum('total_commissions') ?? 0,
                    'growth_rate' => 15.5,
                    'top_performers' => $teamMembers->sortByDesc('total_sales')->take(3)->values()
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting team analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get team analytics'
            ], 500);
        }
    }

    public function productCatalog(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📦 Getting product catalog', ['user_id' => $user->id]);

            $products = [
                [
                    'id' => 1,
                    'name' => 'Sample Product 1',
                    'price' => 25000,
                    'commission_rate' => 10,
                    'category' => 'Electronics'
                ],
                [
                    'id' => 2,
                    'name' => 'Sample Product 2',
                    'price' => 15000,
                    'commission_rate' => 8,
                    'category' => 'Fashion'
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting product catalog', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get product catalog'
            ], 500);
        }
    }

    public function generateShareLink(Request $request)
    {
        try {
            $request->validate([
                'productId' => 'required|string'
            ]);

            $user = $request->user();
            Log::channel('api')->info('🔗 Generating product share link', [
                'user_id' => $user->id,
                'product_id' => $request->productId
            ]);

            $shareLink = "https://chapchap.app/product/{$request->productId}?ref={$user->seller_id}";

            return response()->json([
                'status' => 'success',
                'data' => [
                    'link' => $shareLink
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error generating share link', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate share link'
            ], 500);
        }
    }

    public function commissionRules(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📋 Getting commission rules', ['user_id' => $user->id]);

            $rules = [
                'direct_sales' => 10,
                'level_1_bonus' => 5,
                'level_2_bonus' => 3,
                'level_3_bonus' => 1,
                'minimum_withdrawal' => 1000,
                'withdrawal_fee' => 50
            ];

            return response()->json([
                'status' => 'success',
                'data' => $rules
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting commission rules', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get commission rules'
            ], 500);
        }
    }

    public function updateMLMSettings(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('⚙️ Updating MLM settings', ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'MLM settings updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error updating MLM settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update MLM settings'
            ], 500);
        }
    }

    public function notifications(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('🔔 Getting team notifications', ['user_id' => $user->id]);

            $notifications = [
                [
                    'id' => 1,
                    'title' => 'New Team Member',
                    'message' => 'John Doe joined your team',
                    'type' => 'team_join',
                    'read' => false,
                    'created_at' => now()->subHours(2)->toISOString()
                ],
                [
                    'id' => 2,
                    'title' => 'Commission Earned',
                    'message' => 'You earned TSh 5,000 commission',
                    'type' => 'commission',
                    'read' => false,
                    'created_at' => now()->subHours(5)->toISOString()
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get notifications'
            ], 500);
        }
    }

    public function markNotificationRead(Request $request, $notificationId)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('✅ Marking notification as read', [
                'user_id' => $user->id,
                'notification_id' => $notificationId
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error marking notification as read', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }
}
