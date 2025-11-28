<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FCMService;

class FCMController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function updateToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        try {
            $member = $request->user();
            $member->update([
                'fcm_token' => $request->fcm_token,
            ]);

            Log::channel('api')->info('🔔 FCM token updated', [
                'member_id' => $member->id,
            ]);

            return response()->json([
                'message' => 'FCM token updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Failed to update FCM token', [
                'error' => $e->getMessage(),
                'member_id' => $request->user()->id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update FCM token',
            ], 500);
        }
    }

    public function subscribeToTopic(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
        ]);

        try {
            $member = $request->user();
            Log::channel('api')->info('🔔 Subscribing to FCM topic', [
                'member_id' => $member->id,
                'topic' => $request->topic,
            ]);

            if (!$member->fcm_token) {
                Log::channel('api')->warning('⚠️ FCM token not found for subscription', [
                    'member_id' => $member->id,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'FCM token not found',
                ], 400);
            }

            $this->fcmService->subscribeToTopic($request->topic, $member->fcm_token);

            Log::channel('api')->info('✅ Subscribed to FCM topic', [
                'member_id' => $member->id,
                'topic' => $request->topic,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Subscribed to topic successfully',
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Failed to subscribe to FCM topic', [
                'error' => $e->getMessage(),
                'member_id' => $request->user()->id,
                'topic' => $request->topic,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to subscribe to topic',
            ], 500);
        }
    }

    public function unsubscribeFromTopic(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
        ]);

        try {
            $member = $request->user();
            Log::channel('api')->info('🔕 Unsubscribing from FCM topic', [
                'member_id' => $member->id,
                'topic' => $request->topic,
            ]);

            if (!$member->fcm_token) {
                Log::channel('api')->warning('⚠️ FCM token not found for unsubscription', [
                    'member_id' => $member->id,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'FCM token not found',
                ], 400);
            }

            $this->fcmService->unsubscribeFromTopic($request->topic, $member->fcm_token);

            Log::channel('api')->info('✅ Unsubscribed from FCM topic', [
                'member_id' => $member->id,
                'topic' => $request->topic,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unsubscribed from topic successfully',
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Failed to unsubscribe from FCM topic', [
                'error' => $e->getMessage(),
                'member_id' => $request->user()->id,
                'topic' => $request->topic,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unsubscribe from topic',
            ], 500);
        }
    }
} 