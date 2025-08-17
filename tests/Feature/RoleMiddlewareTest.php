<?php

use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/admin');
    
    $response->assertRedirect(route('login'));
});

test('public users cannot access healthcare routes', function () {
    $publicUser = User::factory()->create();
    
    $this->actingAs($publicUser)
        ->get('/healthcare')
        ->assertStatus(403);
});

test('public users cannot access campaign management routes', function () {
    $publicUser = User::factory()->create();
    
    $this->actingAs($publicUser)
        ->get('/campaigns')
        ->assertStatus(403);
});

test('public users cannot access admin routes', function () {
    $publicUser = User::factory()->create();
    
    $this->actingAs($publicUser)
        ->get('/admin')
        ->assertStatus(403);
});

test('healthcare professionals can access healthcare routes', function () {
    $healthcareProfessional = User::factory()->healthcareProfessional()->create();
    
    $this->actingAs($healthcareProfessional)
        ->get('/healthcare')
        ->assertStatus(200);
});

test('healthcare professionals cannot access campaign management routes', function () {
    $healthcareProfessional = User::factory()->healthcareProfessional()->create();
    
    $this->actingAs($healthcareProfessional)
        ->get('/campaigns')
        ->assertStatus(403);
});

test('healthcare professionals cannot access admin routes', function () {
    $healthcareProfessional = User::factory()->healthcareProfessional()->create();
    
    $this->actingAs($healthcareProfessional)
        ->get('/admin')
        ->assertStatus(403);
});

test('system admins can access all routes', function () {
    $systemAdmin = User::factory()->systemAdmin()->create();
    
    $this->actingAs($systemAdmin)
        ->get('/healthcare')
        ->assertStatus(200);
        
    $this->actingAs($systemAdmin)
        ->get('/campaigns')
        ->assertStatus(200);
        
    $this->actingAs($systemAdmin)
        ->get('/admin')
        ->assertStatus(200);
        
    $this->actingAs($systemAdmin)
        ->get('/admin/users')
        ->assertStatus(200);
});
