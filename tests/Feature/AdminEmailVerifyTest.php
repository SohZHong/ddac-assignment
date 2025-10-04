<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEmailVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_system_admin_can_mark_user_email_as_verified(): void
    {
        $admin = User::factory()->create(['role' => 4]);
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)
            ->from(route('admin.users'))
            ->patch(route('admin.users.verify-email', $user))
            ->assertRedirect(route('admin.users'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_non_admin_cannot_mark_user_email_as_verified(): void
    {
        $nonAdmin = User::factory()->create(['role' => 1]);
        $user = User::factory()->unverified()->create();

        $this->actingAs($nonAdmin)
            ->patch(route('admin.users.verify-email', $user))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }
}


