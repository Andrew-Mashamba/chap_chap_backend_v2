<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MessagingService
{
    protected $smsService;
    protected $emailService;

    public function __construct(SmsService $smsService, EmailService $emailService)
    {
        $this->smsService = $smsService;
        $this->emailService = $emailService;
    }

    public function send(array $data): array
    {
        $phoneNumber = $data['phone'] ?? null;
        $email = $data['email'] ?? null;
        $message = $data['message'] ?? '';
        $subject = $data['subject'] ?? 'ChapChap Notification';
        $preferSms = $data['prefer_sms'] ?? true;
        
        $result = [
            'success' => false,
            'method' => null,
            'error' => null
        ];

        // Validate inputs
        if (empty($message)) {
            $result['error'] = 'Message is required';
            return $result;
        }

        if (empty($phoneNumber) && empty($email)) {
            $result['error'] = 'Either phone number or email is required';
            return $result;
        }

        // Try SMS first if phone number is provided and SMS is preferred
        if ($phoneNumber && $preferSms) {
            Log::info('Attempting to send SMS', ['to' => $phoneNumber]);
            
            if ($this->smsService->sendSms($phoneNumber, $message)) {
                $result['success'] = true;
                $result['method'] = 'sms';
                return $result;
            }

            Log::warning('SMS failed, attempting email fallback', ['phone' => $phoneNumber]);
        }

        // Try email (either as primary method or as fallback)
        if ($email) {
            Log::info('Attempting to send email', ['to' => $email]);
            
            if ($this->emailService->sendEmail($email, $subject, $message)) {
                $result['success'] = true;
                $result['method'] = 'email';
                return $result;
            }
        }

        // If email is not available but SMS was attempted and failed, try SMS to email gateway
        if (!$email && $phoneNumber && !$result['success']) {
            // This is a last resort - some carriers support SMS via email
            // You can implement carrier-specific email gateways here if needed
            Log::error('All messaging methods failed', [
                'phone' => $phoneNumber,
                'email' => $email
            ]);
        }

        $result['error'] = 'Failed to send message via any available method';
        return $result;
    }

    public function sendBulk(array $recipients, string $message, string $subject = 'ChapChap Notification'): array
    {
        $results = [
            'total' => count($recipients),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($recipients as $recipient) {
            $data = [
                'phone' => $recipient['phone'] ?? null,
                'email' => $recipient['email'] ?? null,
                'message' => $message,
                'subject' => $subject,
                'prefer_sms' => $recipient['prefer_sms'] ?? true
            ];

            $result = $this->send($data);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'recipient' => $recipient,
                'result' => $result
            ];
        }

        return $results;
    }
}