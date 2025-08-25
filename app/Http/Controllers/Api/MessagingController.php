<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessagingService;
use Illuminate\Http\Request;
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
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'message' => 'required|string',
            'subject' => 'nullable|string',
            'prefer_sms' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Ensure at least one contact method is provided
        if (!$request->phone && !$request->email) {
            return response()->json([
                'success' => false,
                'message' => 'Either phone number or email is required'
            ], 422);
        }

        $result = $this->messagingService->send($request->all());

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function sendBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*.phone' => 'nullable|string',
            'recipients.*.email' => 'nullable|email',
            'recipients.*.prefer_sms' => 'nullable|boolean',
            'message' => 'required|string',
            'subject' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate each recipient has at least one contact method
        foreach ($request->recipients as $index => $recipient) {
            if (empty($recipient['phone']) && empty($recipient['email'])) {
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

        return response()->json([
            'success' => $results['failed'] === 0,
            'results' => $results
        ]);
    }
}