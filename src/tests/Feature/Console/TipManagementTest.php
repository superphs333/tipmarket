<?php

use App\Models\Role;
use App\Models\User;

function createConsoleUserWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => $roleName,
        'label' => $roleName,
        'description' => $roleName,
    ]);

    $user->roles()->attach($role);

    return $user;
}

test('guests are redirected from the console tips page', function () {
    $this->get(route('console.tips.index'))
        ->assertRedirect(route('login'));
});

test('non console users cannot visit the console tips page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('console.tips.index'))
        ->assertForbidden();
});

test('support users can enter the console but cannot manage tips', function () {
    $support = createConsoleUserWithRole(Role::SUPPORT);

    $this->actingAs($support)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertDontSee('TIPS');

    $this->actingAs($support)
        ->get(route('console.tips.index'))
        ->assertForbidden();
});

test('content managers can manage tips', function () {
    $contentManager = createConsoleUserWithRole(Role::CONTENT_MANAGER);

    $this->actingAs($contentManager)
        ->get(route('console.dashboard'))
        ->assertOk()
        ->assertSee('TIPS');

    $this->actingAs($contentManager)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('TIPS');
});

test('admin users can manage tips', function () {
    $admin = createConsoleUserWithRole(Role::ADMIN);

    $this->actingAs($admin)
        ->get(route('console.tips.index'))
        ->assertOk()
        ->assertSee('TIPS');
});
