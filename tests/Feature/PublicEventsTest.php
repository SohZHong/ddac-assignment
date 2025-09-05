<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $campaignManager;
    private User $otherCampaignManager;
    private User $systemAdmin;
    private User $regularUser;
    private Event $publishedEvent;
    private Event $draftEvent;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->campaignManager = User::factory()->create([
            'role' => UserRole::HEALTH_CAMPAIGN_MANAGER,
        ]);
        
        $this->otherCampaignManager = User::factory()->create([
            'role' => UserRole::HEALTH_CAMPAIGN_MANAGER,
        ]);
        
        $this->systemAdmin = User::factory()->create([
            'role' => UserRole::SYSTEM_ADMIN,
        ]);
        
        $this->regularUser = User::factory()->create([
            'role' => UserRole::PUBLIC_USER,
        ]);
        
        // Create test events
        $this->publishedEvent = Event::factory()->create([
            'status' => 'published',
            'created_by' => $this->campaignManager->id,
            'title' => 'Test Published Event',
            'description' => 'This is a test published event',
            'type' => 'webinar',
            'start_datetime' => now()->addDays(7),
            'end_datetime' => now()->addDays(7)->addHours(2),
            'is_online' => true,
            'requires_registration' => true,
            'capacity' => 100,
        ]);
        
        $this->draftEvent = Event::factory()->create([
            'status' => 'draft',
            'created_by' => $this->campaignManager->id,
            'title' => 'Test Draft Event',
            'description' => 'This is a test draft event',
            'type' => 'workshop',
            'start_datetime' => now()->addDays(14),
            'end_datetime' => now()->addDays(14)->addHours(3),
            'is_online' => false,
            'requires_registration' => false,
            'capacity' => 50,
        ]);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_public_events()
    {
        $this->get('/public/events')->assertRedirect('/login');
        $this->get('/public/events/' . $this->publishedEvent->id)->assertRedirect('/login');
        $this->get('/public/events-feed')->assertRedirect('/login');
    }

    /** @test */
    public function all_authenticated_users_can_view_public_events_index()
    {
        $users = [
            $this->campaignManager,
            $this->otherCampaignManager,
            $this->systemAdmin,
            $this->regularUser,
        ];

        foreach ($users as $user) {
            $response = $this->actingAs($user)->get('/public/events');
            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => 
                $page->component('Events/PublicIndex')
                    ->has('events')
                    ->where('events.0.id', $this->publishedEvent->id)
                    ->where('events.0.title', 'Test Published Event')
            );
        }
    }

    /** @test */
    public function all_authenticated_users_can_view_individual_published_events()
    {
        $users = [
            $this->campaignManager,
            $this->otherCampaignManager,
            $this->systemAdmin,
            $this->regularUser,
        ];

        foreach ($users as $user) {
            $response = $this->actingAs($user)->get('/public/events/' . $this->publishedEvent->id);
            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => 
                $page->component('Events/PublicShow')
                    ->has('event')
                    ->where('event.id', $this->publishedEvent->id)
                    ->where('event.title', 'Test Published Event')
            );
        }
    }

    /** @test */
    public function users_cannot_view_draft_events_via_public_routes()
    {
        $users = [
            $this->campaignManager,
            $this->otherCampaignManager,
            $this->systemAdmin,
            $this->regularUser,
        ];

        foreach ($users as $user) {
            $response = $this->actingAs($user)->get('/public/events/' . $this->draftEvent->id);
            $response->assertStatus(404);
        }
    }

    /** @test */
    public function all_authenticated_users_can_access_public_events_feed()
    {
        $users = [
            $this->campaignManager,
            $this->otherCampaignManager,
            $this->systemAdmin,
            $this->regularUser,
        ];

        foreach ($users as $user) {
            $response = $this->actingAs($user)->get('/public/events-feed');
            $response->assertStatus(200);
            $response->assertJsonStructure(['events']);
            $response->assertJsonCount(1, 'events');
            $response->assertJsonPath('events.0.id', $this->publishedEvent->id);
        }
    }

    /** @test */
    public function public_events_only_show_published_events()
    {
        $response = $this->actingAs($this->regularUser)->get('/public/events');
        
        $response->assertInertia(fn ($page) => 
            $page->component('Events/PublicIndex')
                ->has('events', 1)
                ->where('events.0.status', 'published')
        );
    }

    /** @test */
    public function public_events_only_show_future_events()
    {
        // Create a past published event
        $pastEvent = Event::factory()->create([
            'status' => 'published',
            'created_by' => $this->campaignManager->id,
            'start_datetime' => now()->subDays(1),
            'end_datetime' => now()->subDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($this->regularUser)->get('/public/events');
        
        $response->assertInertia(fn ($page) => 
            $page->component('Events/PublicIndex')
                ->has('events', 1)
                ->where('events.0.id', $this->publishedEvent->id)
        );
    }

    /** @test */
    public function public_events_api_routes_work_correctly()
    {
        $users = [
            $this->campaignManager,
            $this->otherCampaignManager,
            $this->systemAdmin,
            $this->regularUser,
        ];

        foreach ($users as $user) {
            // Test API index - use web authentication for testing
            $response = $this->actingAs($user)->get('/api/public/events');
            $response->assertStatus(200);
            $response->assertJsonCount(1);
            $response->assertJsonPath('0.id', $this->publishedEvent->id);

            // Test API show - use web authentication for testing
            $response = $this->actingAs($user)->get('/api/public/events/' . $this->publishedEvent->id);
            $response->assertStatus(200);
            $response->assertJsonPath('event.id', $this->publishedEvent->id);

            // Test API feed - use web authentication for testing
            $response = $this->actingAs($user)->get('/api/public/events-feed');
            $response->assertStatus(200);
            $response->assertJsonStructure(['events']);
        }
    }

    /** @test */
    public function public_events_show_correct_event_data()
    {
        $response = $this->actingAs($this->regularUser)->get('/public/events/' . $this->publishedEvent->id);
        
        $response->assertInertia(fn ($page) => 
            $page->component('Events/PublicShow')
                ->has('event')
                ->where('event.title', 'Test Published Event')
                ->where('event.description', 'This is a test published event')
                ->where('event.type', 'webinar')
                ->where('event.status', 'published')
                ->where('event.is_online', true)
                ->where('event.requires_registration', true)
                ->where('event.capacity', 100)
        );
    }
}
