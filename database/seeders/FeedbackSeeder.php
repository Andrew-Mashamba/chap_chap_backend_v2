<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feedback;
use App\Models\Member;
use Carbon\Carbon;

class FeedbackSeeder extends Seeder
{
    public function run()
    {
        $members = Member::where('status', 'active')->get();

        if ($members->isEmpty()) {
            return;
        }

        $feedbackTypes = ['complaint', 'suggestion', 'inquiry', 'compliment', 'bug_report'];
        $statuses = ['pending', 'in_progress', 'resolved', 'closed'];

        $feedbackMessages = [
            'complaint' => [
                'Late delivery of my order. It took 5 days instead of 2 days as promised.',
                'Product quality not as described. The phone screen has scratches.',
                'Unable to withdraw my commission. The system shows an error.',
                'My referral code is not working for new members.',
            ],
            'suggestion' => [
                'Please add more payment options like PayPal.',
                'It would be great to have a dark mode in the app.',
                'Can you add product comparison feature?',
                'Please consider adding bulk order discounts.',
            ],
            'inquiry' => [
                'How do I upgrade my seller level from bronze to silver?',
                'What is the minimum withdrawal amount?',
                'Can I change my sponsor after registration?',
                'How are commissions calculated for team sales?',
            ],
            'compliment' => [
                'Great app! Very easy to use and navigate.',
                'Excellent customer service. My issue was resolved quickly.',
                'Love the new features in the latest update.',
                'The commission system is very transparent and fair.',
            ],
            'bug_report' => [
                'App crashes when trying to view order history.',
                'Profile picture upload not working on Android.',
                'Notification settings not saving properly.',
                'Search function returns incorrect results.',
            ],
        ];

        foreach ($members->take(6) as $member) {
            $numFeedback = rand(1, 3);
            
            for ($i = 0; $i < $numFeedback; $i++) {
                $type = $feedbackTypes[array_rand($feedbackTypes)];
                $status = $statuses[array_rand($statuses)];
                $createdAt = Carbon::now()->subDays(rand(1, 30));
                $messages = $feedbackMessages[$type];
                
                Feedback::create([
                    'member_id' => $member->id,
                    'type' => $type,
                    'subject' => ucfirst(str_replace('_', ' ', $type)) . ' - ' . fake()->sentence(3),
                    'message' => $messages[array_rand($messages)],
                    'status' => $status,
                    'priority' => $type === 'bug_report' || $type === 'complaint' ? 'high' : 'normal',
                    'response' => $status === 'resolved' ? 'Thank you for your feedback. Your issue has been addressed and resolved.' : null,
                    'resolved_at' => $status === 'resolved' ? $createdAt->addDays(rand(1, 3)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $status === 'resolved' ? $createdAt->addDays(rand(1, 3)) : $createdAt,
                ]);
            }
        }
    }
}