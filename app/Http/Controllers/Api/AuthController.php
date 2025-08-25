<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function checkPhone(Request $request)
    {
        $requestId = (string) Str::uuid();
        $start = microtime(true);

        Log::info('🔍 Checking phone number', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'route' => $request->path(),
            'phone_number' => $request->phone_number,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $request->validate([
                'phone_number' => 'required|string',
            ]);

            // Check if phone number is blocked
            if ($this->isPhoneNumberBlocked($request->phone_number)) {
                Log::warning('🚫 Blocked phone number attempt', [
                    'request_id' => $requestId,
                    'phone_number' => $request->phone_number,
                    'ip' => $request->ip()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'This phone number has been blocked. Please contact support at 0754 244 888.',
                ], 403);
            }

            $member = Member::where('phone_number', $request->phone_number)->first();

            Log::info('📱 Phone check result', [
                'request_id' => $requestId,
                'phone_number' => $request->phone_number,
                'is_registered' => $member !== null,
                'member_id' => $member?->id
            ]);

            $response = [
                'status' => 'success',
                'is_registered' => $member !== null,
                'member_id' => $member?->id,
                'registration_allowed' => true,
            ];

            Log::info('✅ Phone check completed', [
                'request_id' => $requestId,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                'response' => [
                    'status' => $response['status'],
                    'is_registered' => $response['is_registered'],
                    'member_id' => $response['member_id'],
                ],
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('❌ Phone check failed', [
                'request_id' => $requestId,
                'phone_number' => $request->phone_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);

            throw $e;
        }
    }

    public function login(Request $request)
    {
        Log::info('🔑 Login attempt', [
            'phone' => $request->phone ?? $request->phone_number,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string',
            ]);

            $member = Member::where('phone_number', $request->phone)->first();

            if (!$member) {
                Log::warning('⚠️ Login failed: Member not found', [
                    'phone' => $request->phone
                ]);
                throw ValidationException::withMessages([
                    'phone' => ['The provided credentials are incorrect.'],
                ]);
            }

            if (!Hash::check($request->password, $member->pin)) {
                Log::warning('⚠️ Login failed: Invalid password', [
                    'phone' => $request->phone,
                    'member_id' => $member->id
                ]);
                throw ValidationException::withMessages([
                    'phone' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $member->createToken('auth_token')->plainTextToken;
            $refreshToken = $member->createToken('refresh_token')->plainTextToken;

            Log::info('✅ Login successful', [
                'member_id' => $member->id,
                'phone_number' => $member->phone_number,
                'seller_id' => $member->seller_id
            ]);

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'user' => $member,
            ]);
        } catch (ValidationException $e) {
            Log::warning('⚠️ Login validation failed', [
                'phone' => $request->phone,
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Login failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function refresh(Request $request)
    {
        try {
            // Since this is a public route, get user from the Authorization header
            $user = $request->user('sanctum');
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            Log::info('🔄 Token refresh attempt', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            $member = $user;

            // Revoke old tokens
            $member->tokens()->delete();
            Log::info('🗑️ Old tokens revoked', ['user_id' => $member->id]);

            // Create new tokens
            $token = $member->createToken('auth_token')->plainTextToken;
            $refreshToken = $member->createToken('refresh_token')->plainTextToken;

            Log::info('✅ Token refresh successful', ['user_id' => $member->id]);

            return response()->json([
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Token refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        Log::info('🚪 Logout attempt', [
            'user_id' => $user->id,
            'ip' => $request->ip()
        ]);

        try {
            $request->user()->currentAccessToken()->delete();
            Log::info('✅ Logout successful', ['user_id' => $user->id]);

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            Log::error('❌ Logout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function sendOtp(Request $request)
    {
        Log::info('📱 OTP send request', [
            'phone_number' => $request->phone_number,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $request->validate([
                'phone_number' => 'required|string',
                'message' => 'required|string',
            ]);

            // Check if phone number is blocked
            if ($this->isPhoneNumberBlocked($request->phone_number)) {
                Log::warning('🚫 Blocked phone number OTP attempt', [
                    'phone_number' => $request->phone_number,
                    'ip' => $request->ip()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'This phone number has been blocked. Please contact support at 0754 244 888.',
                ], 403);
            }

            // Use the messaging service to send OTP
            $messagingService = app(\App\Services\MessagingService::class);
            $result = $messagingService->send([
                'phone' => $request->phone_number,
                'message' => $request->message,
                'subject' => $request->subject ?? 'ChapChap Verification Code',
                'prefer_sms' => true,
            ]);

            Log::info('📱 OTP send result', [
                'phone_number' => $request->phone_number,
                'success' => $result['success']
            ]);

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('❌ OTP send failed', [
                'phone_number' => $request->phone_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send OTP. Please try again or contact support at 0754 244 888.',
            ], 500);
        }
    }

    public function verifySponsor(Request $request)
    {
        try {
            Log::info('🔍 Verifying sponsor code', [
                'sponsor_code' => $request->sponsor_code,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $request->validate([
                'sponsor_code' => 'required|string',
            ]);

            // Check if sponsor code is blocked
            if ($this->isSponsorIdBlocked($request->sponsor_code)) {
                Log::warning('🚫 Blocked sponsor code attempt', [
                    'sponsor_code' => $request->sponsor_code,
                    'ip' => $request->ip()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'This sponsor code has been blocked. Please contact support at 0754 244 888.',
                ], 403);
            }

            $sponsor = Member::where('seller_id', $request->sponsor_code)
                ->where('account_status', 'active')
                ->first();

            if (!$sponsor) {
                Log::warning('❌ Invalid sponsor code', [
                    'sponsor_code' => $request->sponsor_code,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid sponsor code. Please contact support at 0754 244 888 if you don\'t have a sponsor.',
                ], 404);
            }

            // Check if sponsor has reached maximum downlines
            $downlineCount = $sponsor->downlines()->count();
            $maxDownlines = $this->getMaxDownlinesForLevel($sponsor->seller_level);
            
            if ($downlineCount >= $maxDownlines) {
                Log::warning('❌ Sponsor has reached maximum downlines', [
                    'sponsor_id' => $sponsor->seller_id,
                    'current_downlines' => $downlineCount,
                    'max_downlines' => $maxDownlines,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'This sponsor has reached their maximum number of downlines. Please contact support at 0754 244 888.',
                ], 422);
            }

            Log::info('✅ Sponsor verified', [
                'sponsor_id' => $sponsor->seller_id,
                'name' => $sponsor->first_name . ' ' . $sponsor->last_name,
                'level' => $sponsor->seller_level,
                'downlines' => $downlineCount,
                'max_downlines' => $maxDownlines,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'first_name' => $sponsor->first_name,
                    'last_name' => $sponsor->last_name,
                    'seller_id' => $sponsor->seller_id,
                    'level' => $sponsor->seller_level,
                    'commission_rate' => $sponsor->commission_rate,
                    'downlines_count' => $downlineCount,
                    'max_downlines' => $maxDownlines,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Sponsor verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify sponsor. Please try again or contact support at 0754 244 888.',
            ], 500);
        }
    }

    public function register(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('📝 Registration attempt', [
                'phone' => $request->phone_number,
                'sponsor_code' => $request->sponsor_code,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Check for registration rate limiting
            if ($this->isRegistrationRateLimited($request->ip())) {
                Log::warning('🚫 Registration rate limit exceeded', [
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many registration attempts. Please try again later or contact support at 0754 244 888.',
                ], 429);
            }

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'required|string|unique:members',
                'pin' => 'required|string|min:4|max:4|regex:/^\d{4}$/',
                'email' => 'nullable|email|unique:members',
                'shop_name' => 'required|string|max:255',
                'shop_location' => 'required|string|max:255',
                'sponsor_code' => 'required|string|exists:members,seller_id',
                'firebase_uid' => 'required|string',
                'profile_image' => 'nullable', // Allow string for testing, file for production
            ]);

            // Verify sponsor exists and is active
            $sponsor = Member::where('seller_id', $request->sponsor_code)
                ->where('account_status', 'active')
                ->first();

            if (!$sponsor) {
                Log::warning('❌ Invalid sponsor ID during registration', [
                    'sponsor_code' => $request->sponsor_code,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid sponsor ID. Please contact support at 0754 244 888.',
                ], 422);
            }

            // Check sponsor's downline limit
            $downlineCount = $sponsor->downlines()->count();
            $maxDownlines = $this->getMaxDownlinesForLevel($sponsor->seller_level);
            
            if ($downlineCount >= $maxDownlines) {
                Log::warning('❌ Registration failed: Sponsor has reached maximum downlines', [
                    'sponsor_id' => $sponsor->seller_id,
                    'current_downlines' => $downlineCount,
                    'max_downlines' => $maxDownlines,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'This sponsor has reached their maximum number of downlines. Please contact support at 0754 244 888.',
                ], 422);
            }

            // Generate unique seller ID
            do {
                $lastMember = Member::orderBy('id', 'desc')->first();
                $nextId = $lastMember ? $lastMember->id + 1 : 1;
                $sellerId = 'SLR' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
                // Check if seller_id already exists
                $existing = Member::where('seller_id', $sellerId)->first();
                if ($existing) {
                    // If exists, increment by 1000 to avoid conflicts
                    $nextId += 1000;
                    $sellerId = 'SLR' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
                }
            } while (Member::where('seller_id', $sellerId)->exists());

            // Handle profile image upload
            $imagePath = null;
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            } elseif ($request->profile_image) {
                $imagePath = $request->profile_image; // For testing
            }
            
            // Create new member
            $member = Member::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'full_name' => $request->first_name . ' ' . $request->last_name,
                'phone_number' => $request->phone_number,
                'pin' => Hash::make($request->pin),
                'email' => $request->email,
                'firebase_uid' => $request->firebase_uid,
                'shop_name' => $request->shop_name,
                'district' => $request->shop_location,
                'seller_id' => $sellerId,
                'photo_path' => $imagePath,
                'upline_id' => $sponsor->seller_id,
                'account_status' => 'active',
                'seller_level' => 1,
            ]);

            // Generate tokens
            $token = $member->createToken('auth_token')->plainTextToken;
            $refreshToken = $member->createToken('refresh_token')->plainTextToken;

            // Update sponsor's downline count
            $sponsor->increment('total_downlines');

            DB::commit();

            Log::info('✅ Registration successful', [
                'member_id' => $member->id,
                'seller_id' => $member->seller_id,
                'sponsor_id' => $sponsor->seller_id,
                'sponsor_level' => $sponsor->seller_level,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => [
                    'member' => $member,
                    'access_token' => $token,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'sponsor' => [
                        'id' => $sponsor->seller_id,
                        'name' => $sponsor->first_name . ' ' . $sponsor->last_name,
                        'level' => $sponsor->seller_level,
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('❌ Registration validation failed', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed. Please try again or contact support at 0754 244 888.',
            ], 500);
        }
    }

    private function isPhoneNumberBlocked($phoneNumber)
    {
        // Implement phone number blocking logic
        return false;
    }

    private function isSponsorIdBlocked($sponsorId)
    {
        // Implement sponsor ID blocking logic
        return false;
    }

    private function isRegistrationRateLimited($ip)
    {
        // Implement rate limiting logic (e.g., max 5 registrations per hour per IP)
        return false;
    }

    private function getMaxDownlinesForLevel($level)
    {
        // Define maximum downlines based on seller level
        $maxDownlines = [
            1 => 5,    // Level 1: 5 downlines
            2 => 10,   // Level 2: 10 downlines
            3 => 20,   // Level 3: 20 downlines
            4 => 50,   // Level 4: 50 downlines
            5 => 100,  // Level 5: 100 downlines
        ];

        return $maxDownlines[$level] ?? 5; // Default to 5 if level not found
    }
}
