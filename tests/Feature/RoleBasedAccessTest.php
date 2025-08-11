<?php

use App\Models\User;
use App\UserRole;

test('users have correct default role', function () {
    $user = User::factory()->create();
    
    expect($user->role)->toBe(UserRole::PUBLIC_USER);
    expect($user->isPublicUser())->toBeTrue();
});

test('user role helper methods work correctly', function () {
    $publicUser = User::factory()->create();
    $healthcareProfessional = User::factory()->healthcareProfessional()->create();
    $healthCampaignManager = User::factory()->healthCampaignManager()->create();
    $systemAdmin = User::factory()->systemAdmin()->create();
    
    // Test public user
    expect($publicUser->isPublicUser())->toBeTrue();
    expect($publicUser->isHealthcareProfessional())->toBeFalse();
    expect($publicUser->isHealthCampaignManager())->toBeFalse();
    expect($publicUser->isSystemAdmin())->toBeFalse();
    
    // Test healthcare professional
    expect($healthcareProfessional->isPublicUser())->toBeFalse();
    expect($healthcareProfessional->isHealthcareProfessional())->toBeTrue();
    expect($healthcareProfessional->isHealthCampaignManager())->toBeFalse();
    expect($healthcareProfessional->isSystemAdmin())->toBeFalse();
    
    // Test health campaign manager
    expect($healthCampaignManager->isPublicUser())->toBeFalse();
    expect($healthCampaignManager->isHealthcareProfessional())->toBeFalse();
    expect($healthCampaignManager->isHealthCampaignManager())->toBeTrue();
    expect($healthCampaignManager->isSystemAdmin())->toBeFalse();
    
    // Test system admin
    expect($systemAdmin->isPublicUser())->toBeFalse();
    expect($systemAdmin->isHealthcareProfessional())->toBeFalse();
    expect($systemAdmin->isHealthCampaignManager())->toBeFalse();
    expect($systemAdmin->isSystemAdmin())->toBeTrue();
});

test('role management permissions work correctly', function () {
    $publicUser = User::factory()->create();
    $healthcareProfessional = User::factory()->healthcareProfessional()->create();
    $healthCampaignManager = User::factory()->healthCampaignManager()->create();
    $systemAdmin = User::factory()->systemAdmin()->create();
    
    // Only managers and admins can manage roles
    expect($publicUser->canManageRoles())->toBeFalse();
    expect($healthcareProfessional->canManageRoles())->toBeFalse();
    expect($healthCampaignManager->canManageRoles())->toBeTrue();
    expect($systemAdmin->canManageRoles())->toBeTrue();
});

test('assignable roles permissions work correctly', function () {
    $publicUser = User::factory()->create();
    $healthcareProfessional = User::factory()->healthcareProfessional()->create();
    $healthCampaignManager = User::factory()->healthCampaignManager()->create();
    $systemAdmin = User::factory()->systemAdmin()->create();
    
    // Public users and healthcare professionals cannot assign roles
    expect($publicUser->getAssignableRoles())->toBeEmpty();
    expect($healthcareProfessional->getAssignableRoles())->toBeEmpty();
    
    // Health campaign managers can assign public user and healthcare professional roles
    expect($healthCampaignManager->getAssignableRoles())->toContain(UserRole::PUBLIC_USER);
    expect($healthCampaignManager->getAssignableRoles())->toContain(UserRole::HEALTHCARE_PROFESSIONAL);
    expect($healthCampaignManager->getAssignableRoles())->not->toContain(UserRole::HEALTH_CAMPAIGN_MANAGER);
    expect($healthCampaignManager->getAssignableRoles())->not->toContain(UserRole::SYSTEM_ADMIN);
    
    // System admins can assign all roles
    expect($systemAdmin->getAssignableRoles())->toContain(UserRole::PUBLIC_USER);
    expect($systemAdmin->getAssignableRoles())->toContain(UserRole::HEALTHCARE_PROFESSIONAL);
    expect($systemAdmin->getAssignableRoles())->toContain(UserRole::HEALTH_CAMPAIGN_MANAGER);
    expect($systemAdmin->getAssignableRoles())->toContain(UserRole::SYSTEM_ADMIN);
});

test('has role method works correctly', function () {
    $user = User::factory()->healthCampaignManager()->create();
    
    expect($user->hasRole(UserRole::HEALTH_CAMPAIGN_MANAGER))->toBeTrue();
    expect($user->hasRole(UserRole::PUBLIC_USER))->toBeFalse();
});

test('has any role method works correctly', function () {
    $user = User::factory()->healthcareProfessional()->create();
    
    expect($user->hasAnyRole([UserRole::HEALTHCARE_PROFESSIONAL, UserRole::SYSTEM_ADMIN]))->toBeTrue();
    expect($user->hasAnyRole([UserRole::PUBLIC_USER, UserRole::SYSTEM_ADMIN]))->toBeFalse();
});

test('user role enum labels work correctly', function () {
    expect(UserRole::PUBLIC_USER->label())->toBe('Public User');
    expect(UserRole::HEALTHCARE_PROFESSIONAL->label())->toBe('Healthcare Professional');
    expect(UserRole::HEALTH_CAMPAIGN_MANAGER->label())->toBe('Health Campaign Manager');
    expect(UserRole::SYSTEM_ADMIN->label())->toBe('System Administrator');
});
