<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a user with default attributes', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBeString()->not->toBeEmpty()
        ->and($user->email)->toBeString()->toContain('@')
        ->and($user->password)->toBeString()->not->toBeEmpty()
        ->and($user->remember_token)->toBeString()->toHaveLength(60);
});

it('creates multiple users with unique emails', function () {
    $users = User::factory()->count(3)->create();

    expect($users)->toHaveCount(3);

    $emails = $users->pluck('email')->toArray();
    expect($emails)->toHaveCount(3)
        ->and(array_unique($emails))->toHaveCount(3);
});

it('creates a user with hashed password', function () {
    $user = User::factory()->create();

    expect(Hash::check('secret', $user->password))->toBeTrue();
});

it('creates a user without persisting to database using make', function () {
    $user = User::factory()->make();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->exists)->toBeFalse()
        ->and($user->name)->not->toBeEmpty();
});

it('reuses the same password for multiple users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->password)->toBe($user2->password);
});

it('creates roles before creating user', function () {
    User::factory()->create();

    expect(Role::all())->not->toBeEmpty();
});

it('affect REGISTERED role to defaut user', function () {
    $user = User::factory()->create();

    expect($user->hasRole(Role::REGISTERED))->toBeTrue();
});

it('creates ADMIN user', function () {
    $user = User::factory()->admin()->create();

    expect($user->hasRole(Role::ADMIN))->toBeTrue();
});
