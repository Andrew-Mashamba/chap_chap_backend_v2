<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    public function feedback(Request $request)
    {
        try {
            $user = $request->user();
            Log::channel('api')->info('📝 Submitting feedback', [
                'user_id' => $user->id,
                'rating' => $request->rating
            ]);

            $validated = $request->validate([
                'message' => 'required|string|min:10',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            $feedback = Feedback::create([
                'member_id' => $user->id,
                'message' => $validated['message'],
                'rating' => $validated['rating'],
            ]);

            Log::channel('api')->info('✅ Feedback submitted successfully', [
                'user_id' => $user->id,
                'feedback_id' => $feedback->id,
                'rating' => $validated['rating']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback submitted successfully',
                'data' => $feedback
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('api')->warning('⚠️ Feedback validation failed', [
                'user_id' => $request->user()?->id,
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error submitting feedback', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit feedback'
            ], 500);
        }
    }
}
