<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function sendEmail(string $to, string $subject, string $message): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)
                     ->subject($subject);
            });

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Email send error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendHtmlEmail(string $to, string $subject, string $htmlContent): bool
    {
        try {
            Mail::html($htmlContent, function ($mail) use ($to, $subject) {
                $mail->to($to)
                     ->subject($subject);
            });

            Log::info('HTML Email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('HTML Email send error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}