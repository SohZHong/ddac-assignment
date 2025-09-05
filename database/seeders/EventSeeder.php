<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Campaign;
use App\Models\User;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a campaign manager user
        $user = User::where('role', 3)->first();

        if (!$user) {
            $this->command->info('No campaign manager found. Creating one...');
            $user = User::create([
                'name' => 'Campaign Manager',
                'email' => 'campaign@example.com',
                'password' => bcrypt('password'),
                'role' => 3,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]);
        }

        // Get some campaigns to associate with events
        $campaigns = Campaign::where('created_by', $user->id)->get();

        // Create some sample events
        $events = [
            [
                'title' => 'Diabetes Prevention Webinar',
                'description' => 'Join us for an informative webinar about diabetes prevention strategies, healthy eating habits, and lifestyle modifications to reduce your risk.',
                'type' => 'webinar',
                'status' => 'published',
                'start_datetime' => now()->addDays(7)->setTime(14, 0),
                'end_datetime' => now()->addDays(7)->setTime(15, 30),
                'location' => null,
                'online_meeting_url' => 'https://meet.google.com/diabetes-prevention-2024',
                'capacity' => 100,
                'is_online' => true,
                'requires_registration' => true,
                'campaign_id' => $campaigns->where('title', 'Diabetes Prevention Awareness')->first()?->id,
                'created_by' => $user->id,
            ],
            [
                'title' => 'Mental Health Workshop',
                'description' => 'A hands-on workshop focusing on stress management techniques, mindfulness practices, and building resilience for better mental health.',
                'type' => 'workshop',
                'status' => 'published',
                'start_datetime' => now()->addDays(14)->setTime(10, 0),
                'end_datetime' => now()->addDays(14)->setTime(16, 0),
                'location' => 'Community Health Center, 123 Main St',
                'online_meeting_url' => null,
                'capacity' => 25,
                'is_online' => false,
                'requires_registration' => true,
                'campaign_id' => $campaigns->where('title', 'Mental Health Support Initiative')->first()?->id,
                'created_by' => $user->id,
            ],
            [
                'title' => 'Annual Health Check-up Drive',
                'description' => 'Free health screenings including blood pressure, cholesterol, and diabetes testing. Open to all community members.',
                'type' => 'check_up_drive',
                'status' => 'draft',
                'start_datetime' => now()->addDays(30)->setTime(9, 0),
                'end_datetime' => now()->addDays(30)->setTime(17, 0),
                'location' => 'City Hall Auditorium, 456 Oak Ave',
                'online_meeting_url' => null,
                'capacity' => 200,
                'is_online' => false,
                'requires_registration' => false,
                'campaign_id' => $campaigns->where('title', 'Vaccination Drive 2024')->first()?->id,
                'created_by' => $user->id,
            ],
            [
                'title' => 'Nutrition Education Seminar',
                'description' => 'Learn about balanced nutrition, meal planning, and healthy cooking techniques for better health outcomes.',
                'type' => 'seminar',
                'status' => 'completed',
                'start_datetime' => now()->subDays(7)->setTime(15, 0),
                'end_datetime' => now()->subDays(7)->setTime(17, 0),
                'location' => 'Public Library, 789 Pine St',
                'online_meeting_url' => null,
                'capacity' => 50,
                'is_online' => false,
                'requires_registration' => true,
                'campaign_id' => $campaigns->where('title', 'Nutrition Education Program')->first()?->id,
                'created_by' => $user->id,
            ],
            [
                'title' => 'Senior Fitness Class',
                'description' => 'Low-impact exercise class designed specifically for seniors to improve mobility, strength, and overall fitness.',
                'type' => 'health_event',
                'status' => 'ongoing',
                'start_datetime' => now()->setTime(9, 0),
                'end_datetime' => now()->setTime(10, 0),
                'location' => 'Senior Center, 321 Elm St',
                'online_meeting_url' => null,
                'capacity' => 30,
                'is_online' => false,
                'requires_registration' => true,
                'campaign_id' => $campaigns->where('title', 'Exercise for Seniors')->first()?->id,
                'created_by' => $user->id,
            ]
        ];

        foreach ($events as $eventData) {
            Event::create($eventData);
        }

        $this->command->info('Created ' . count($events) . ' sample events for user: ' . $user->name);
    }
}
