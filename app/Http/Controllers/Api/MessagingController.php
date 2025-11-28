<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MessagingController extends Controller
{
    protected $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    public function send(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📨 Sending message', [
                'user_id' => $user->id,
                'has_phone' => !empty($request->phone),
                'has_email' => !empty($request->email),
                'prefer_sms' => $request->prefer_sms ?? false
            ]);

            $validator = Validator::make($request->all(), [
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
                'message' => 'required|string',
                'subject' => 'nullable|string',
                'prefer_sms' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                Log::channel('api')->warning('⚠️ Message validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure at least one contact method is provided
            if (!$request->phone && !$request->email) {
                Log::channel('api')->warning('⚠️ No contact method provided', [
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Either phone number or email is required'
                ], 422);
            }

            $result = $this->messagingService->send($request->all());

            Log::channel('api')->info($result['success'] ? '✅ Message sent successfully' : '❌ Message send failed', [
                'user_id' => $user->id,
                'success' => $result['success']
            ]);

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error sending message', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    public function sendBulk(Request $request)
    {
        try {
            $user = $request->user();
            $recipientCount = is_array($request->recipients) ? count($request->recipients) : 0;

            Log::channel('api')->info('📨 Sending bulk message', [
                'user_id' => $user->id,
                'recipient_count' => $recipientCount
            ]);

            $validator = Validator::make($request->all(), [
                'recipients' => 'required|array|min:1',
                'recipients.*.phone' => 'nullable|string',
                'recipients.*.email' => 'nullable|email',
                'recipients.*.prefer_sms' => 'nullable|boolean',
                'message' => 'required|string',
                'subject' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                Log::channel('api')->warning('⚠️ Bulk message validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate each recipient has at least one contact method
            foreach ($request->recipients as $index => $recipient) {
                if (empty($recipient['phone']) && empty($recipient['email'])) {
                    Log::channel('api')->warning('⚠️ Invalid recipient in bulk message', [
                        'user_id' => $user->id,
                        'recipient_index' => $index
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "Recipient at index $index must have either phone or email"
                    ], 422);
                }
            }

            $results = $this->messagingService->sendBulk(
                $request->recipients,
                $request->message,
                $request->subject ?? 'ChapChap Notification'
            );

            Log::channel('api')->info('✅ Bulk message processed', [
                'user_id' => $user->id,
                'sent' => $results['sent'] ?? 0,
                'failed' => $results['failed'] ?? 0
            ]);

            return response()->json([
                'success' => $results['failed'] === 0,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error sending bulk message', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk message'
            ], 500);
        }
    }
}