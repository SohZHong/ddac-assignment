<?php

use App\Models\Campaign;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

uses(RefreshDatabase::class, WithFaker::class);

test('campaign managers can view campaigns index', function () {
    $user = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->get('/campaigns');

    $response->assertStatus(200);
});

test('campaign managers can only see their own campaigns', function () {
    $user1 = User::factory()->healthCampaignManager()->create();
    $user2 = User::factory()->healthCampaignManager()->create();
    
    $campaign1 = Campaign::factory()->create(['created_by' => $user1->id]);
    $campaign2 = Campaign::factory()->create(['created_by' => $user2->id]);

    $response = $this->actingAs($user1)->get('/campaigns');

    $response->assertStatus(200);
});

test('system admins can see all campaigns', function () {
    $admin = User::factory()->systemAdmin()->create();
    $campaignManager = User::factory()->healthCampaignManager()->create();
    
    $campaign1 = Campaign::factory()->create(['created_by' => $admin->id]);
    $campaign2 = Campaign::factory()->create(['created_by' => $campaignManager->id]);

    $response = $this->actingAs($admin)->get('/campaigns');

    $response->assertStatus(200);
});

test('campaign managers can create campaigns', function () {
    $user = User::factory()->healthCampaignManager()->create();
    
    $campaignData = [
        'title' => 'Test Campaign',
        'description' => 'This is a test campaign',
        'type' => 'awareness',
        'status' => 'draft',
        'start_date' => now()->addDays(7)->format('Y-m-d'),
        'end_date' => now()->addDays(30)->format('Y-m-d'),
        'target_audience' => 'General Public',
        'target_reach' => 1000,
        'budget' => 5000.00,
        'location' => 'Online',
    ];

    $response = $this->actingAs($user)->post('/campaigns', $campaignData);

    $response->assertRedirect('/campaigns');
    $this->assertDatabaseHas('campaigns', [
        'title' => 'Test Campaign',
        'created_by' => $user->id,
    ]);
});

test('campaign creation validates required fields', function () {
    $user = User::factory()->healthCampaignManager()->create();

    $response = $this->actingAs($user)->post('/campaigns', []);

    $response->assertSessionHasErrors(['title', 'description', 'type', 'status', 'start_date', 'end_date']);
});

test('campaign creation validates date logic', function () {
    $user = User::factory()->healthCampaignManager()->create();
    
    $campaignData = [
        'title' => 'Test Campaign',
        'description' => 'This is a test campaign',
        'type' => 'awareness',
        'status' => 'draft',
        'start_date' => now()->addDays(30)->format('Y-m-d'),
        'end_date' => now()->addDays(7)->format('Y-m-d'), // End date before start date
    ];

    $response = $this->actingAs($user)->post('/campaigns', $campaignData);

    $response->assertSessionHasErrors(['end_date']);
});

test('campaign managers can view their own campaigns', function () {
    $user = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->get("/campaigns/{$campaign->id}");

    $response->assertStatus(200);
});

test('campaign managers cannot view other campaigns', function () {
    $user1 = User::factory()->healthCampaignManager()->create();
    $user2 = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user2->id]);

    $response = $this->actingAs($user1)->get("/campaigns/{$campaign->id}");

    $response->assertStatus(403);
});

test('system admins can view any campaign', function () {
    $admin = User::factory()->systemAdmin()->create();
    $campaignManager = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $campaignManager->id]);

    $response = $this->actingAs($admin)->get("/campaigns/{$campaign->id}");

    $response->assertStatus(200);
});

test('campaign managers can update their own campaigns', function () {
    $user = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user->id]);
    
    $updateData = [
        'title' => 'Updated Campaign Title',
        'description' => 'Updated description',
        'type' => 'awareness',
        'status' => 'active',
        'start_date' => now()->addDays(1)->format('Y-m-d'),
        'end_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $response = $this->actingAs($user)->put("/campaigns/{$campaign->id}", $updateData);

    $response->assertRedirect("/campaigns/{$campaign->id}");
    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'title' => 'Updated Campaign Title',
    ]);
});

test('campaign managers cannot update other campaigns', function () {
    $user1 = User::factory()->healthCampaignManager()->create();
    $user2 = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user2->id]);
    
    $updateData = [
        'title' => 'Updated Campaign Title',
        'description' => 'Updated description',
        'type' => 'awareness',
        'status' => 'active',
        'start_date' => now()->addDays(1)->format('Y-m-d'),
        'end_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $response = $this->actingAs($user1)->put("/campaigns/{$campaign->id}", $updateData);

    $response->assertStatus(403);
});

test('campaign managers can delete their own campaigns', function () {
    $user = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->delete("/campaigns/{$campaign->id}");

    $response->assertRedirect('/campaigns');
    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
});

test('campaign managers cannot delete other campaigns', function () {
    $user1 = User::factory()->healthCampaignManager()->create();
    $user2 = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user2->id]);

    $response = $this->actingAs($user1)->delete("/campaigns/{$campaign->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
});

test('public users cannot access campaigns', function () {
    $user = User::factory()->publicUser()->create();

    $response = $this->actingAs($user)->get('/campaigns');

    $response->assertStatus(403);
});

test('healthcare professionals cannot access campaigns', function () {
    $user = User::factory()->healthcareProfessional()->create();

    $response = $this->actingAs($user)->get('/campaigns');

    $response->assertStatus(403);
});

test('campaign model helper methods work correctly', function () {
    $campaign = Campaign::factory()->create([
        'status' => 'active',
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(25),
    ]);

    $this->assertTrue($campaign->isActive());
    $this->assertFalse($campaign->isDraft());
    $this->assertFalse($campaign->isCompleted());
    $this->assertFalse($campaign->isCancelled());
    $this->assertTrue($campaign->isRunning());
    $this->assertEquals(30, $campaign->getDurationInDays());
    $this->assertGreaterThan(0, $campaign->getProgressPercentage());
});

test('campaign relationships work correctly', function () {
    $user = User::factory()->healthCampaignManager()->create();
    $campaign = Campaign::factory()->create(['created_by' => $user->id]);

    $this->assertInstanceOf(User::class, $campaign->creator);
    $this->assertEquals($user->id, $campaign->creator->id);
    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $campaign->events);
});
