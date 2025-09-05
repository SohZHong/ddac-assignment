<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\User;
use App\Policies\EventPolicy;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    private EventPolicy $policy;
    private Event $publishedEvent;
    private Event $draftEvent;
    private User $campaignManager;
    private User $otherCampaignManager;
    private User $systemAdmin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new EventPolicy();
        
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
        ]);
        
        $this->draftEvent = Event::factory()->create([
            'status' => 'draft',
            'created_by' => $this->campaignManager->id,
        ]);
    }

    /** @test */
    public function all_authenticated_users_can_view_published_events()
    {
        // Campaign manager can view published event
        $this->assertTrue($this->policy->view($this->campaignManager, $this->publishedEvent));
        
        // Other campaign manager can view published event
        $this->assertTrue($this->policy->view($this->otherCampaignManager, $this->publishedEvent));
        
        // System admin can view published event
        $this->assertTrue($this->policy->view($this->systemAdmin, $this->publishedEvent));
        
        // Regular user can view published event
        $this->assertTrue($this->policy->view($this->regularUser, $this->publishedEvent));
    }

    /** @test */
    public function only_creator_and_admin_can_view_draft_events()
    {
        // Creator can view their own draft event
        $this->assertTrue($this->policy->view($this->campaignManager, $this->draftEvent));
        
        // Other campaign manager cannot view draft event
        $this->assertFalse($this->policy->view($this->otherCampaignManager, $this->draftEvent));
        
        // System admin can view draft event
        $this->assertTrue($this->policy->view($this->systemAdmin, $this->draftEvent));
        
        // Regular user cannot view draft event
        $this->assertFalse($this->policy->view($this->regularUser, $this->draftEvent));
    }

    /** @test */
    public function view_published_policy_allows_all_authenticated_users()
    {
        $this->assertTrue($this->policy->viewPublished($this->campaignManager));
        $this->assertTrue($this->policy->viewPublished($this->otherCampaignManager));
        $this->assertTrue($this->policy->viewPublished($this->systemAdmin));
        $this->assertTrue($this->policy->viewPublished($this->regularUser));
    }

    /** @test */
    public function only_managers_and_admins_can_view_any_events()
    {
        $this->assertTrue($this->policy->viewAny($this->campaignManager));
        $this->assertTrue($this->policy->viewAny($this->otherCampaignManager));
        $this->assertTrue($this->policy->viewAny($this->systemAdmin));
        $this->assertFalse($this->policy->viewAny($this->regularUser));
    }

    /** @test */
    public function only_managers_and_admins_can_create_events()
    {
        $this->assertTrue($this->policy->create($this->campaignManager));
        $this->assertTrue($this->policy->create($this->otherCampaignManager));
        $this->assertTrue($this->policy->create($this->systemAdmin));
        $this->assertFalse($this->policy->create($this->regularUser));
    }

    /** @test */
    public function only_creator_and_admin_can_update_events()
    {
        // Creator can update their own event
        $this->assertTrue($this->policy->update($this->campaignManager, $this->publishedEvent));
        
        // Other campaign manager cannot update event
        $this->assertFalse($this->policy->update($this->otherCampaignManager, $this->publishedEvent));
        
        // System admin can update any event
        $this->assertTrue($this->policy->update($this->systemAdmin, $this->publishedEvent));
        
        // Regular user cannot update events
        $this->assertFalse($this->policy->update($this->regularUser, $this->publishedEvent));
    }

    /** @test */
    public function only_creator_and_admin_can_delete_events()
    {
        // Creator can delete their own event
        $this->assertTrue($this->policy->delete($this->campaignManager, $this->publishedEvent));
        
        // Other campaign manager cannot delete event
        $this->assertFalse($this->policy->delete($this->otherCampaignManager, $this->publishedEvent));
        
        // System admin can delete any event
        $this->assertTrue($this->policy->delete($this->systemAdmin, $this->publishedEvent));
        
        // Regular user cannot delete events
        $this->assertFalse($this->policy->delete($this->regularUser, $this->publishedEvent));
    }

    /** @test */
    public function only_system_admin_can_restore_and_force_delete_events()
    {
        $this->assertTrue($this->policy->restore($this->systemAdmin, $this->publishedEvent));
        $this->assertTrue($this->policy->forceDelete($this->systemAdmin, $this->publishedEvent));
        
        $this->assertFalse($this->policy->restore($this->campaignManager, $this->publishedEvent));
        $this->assertFalse($this->policy->forceDelete($this->campaignManager, $this->publishedEvent));
        
        $this->assertFalse($this->policy->restore($this->regularUser, $this->publishedEvent));
        $this->assertFalse($this->policy->forceDelete($this->regularUser, $this->publishedEvent));
    }
}
