<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = Firebase::messaging();
    }

    public function sendNotification(string $token, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            
            return true;
        } catch (\Exception $e) {
            Log::error('FCM send notification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendToMultiple(array $tokens, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $report = $this->messaging->sendMulticast($message, $tokens);

            Log::info('FCM multicast report', [
                'success_count' => $report->successes()->count(),
                'failure_count' => $report->failures()->count(),
            ]);

            return $report;
        } catch (\Exception $e) {
            Log::error('FCM send multicast error: ' . $e->getMessage());
            return null;
        }
    }

    public function subscribeToTopic(string $token, string $topic)
    {
        try {
            $this->messaging->subscribeToTopic($topic, $token);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM subscribe to topic error: ' . $e->getMessage());
            return false;
        }
    }

    public function unsubscribeFromTopic(string $token, string $topic)
    {
        try {
            $this->messaging->unsubscribeFromTopic($topic, $token);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM unsubscribe from topic error: ' . $e->getMessage());
            return false;
        }
    }
}