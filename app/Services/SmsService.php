<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $twilio;
    protected $from;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.from');
    }

    public function sendSms(string $to, string $message): bool
    {
        try {
            $message = $this->twilio->messages->create(
                $to,
                [
                    'from' => $this->from,
                    'body' => $message
                ]
            );

            Log::info('SMS sent successfully', [
                'to' => $to,
                'sid' => $message->sid,
                'status' => $message->status
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS send error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}