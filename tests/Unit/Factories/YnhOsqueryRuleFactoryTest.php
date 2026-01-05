<?php

namespace Tests\Unit\Factories;

use App\Models\YnhOsqueryRule;
use Illuminate\Support\Facades\Auth;

it('creates a rule with valid attributes', function () {
    $rule = YnhOsqueryRule::factory()->create();

    expect($rule)->toBeInstanceOf(YnhOsqueryRule::class)
        ->and($rule->name)->toBeString()
        ->and($rule->description)->toBeString()
        ->and($rule->enabled)->toBeBool();
});

it('generates unique rule names', function () {
    $rules = YnhOsqueryRule::factory()->count(5)->create();
    $names = $rules->pluck('name')->unique();

    expect($names->count())->toBe(5);
});

test('rule name contains tenant id prefix for non-admin users', function () {
    asTenant1User();
    $tenant_id = tenant1User()->tenant_id;
    
    $rule = YnhOsqueryRule::factory()->create();

    expect($rule->name)->toMatch("/^{$tenant_id}_cywise_[a-z0-9_]+$/");
});

test('rule name does not contain tenant id prefix for admin users', function () {
    asTenant1User();
    becomeCywiseAdmin();
    $tenant_id = tenant1User()->tenant_id;

    $rule = YnhOsqueryRule::factory()->create();

    expect($rule->name)->not->toMatch("/^{$tenant_id}_cywise_[a-z0-9_]+$/");
});

it('assigns created_by to authenticated user', function () {
    asTenant1User();

    $rule = YnhOsqueryRule::factory()->create();

    expect($rule->created_by)->toBe(tenant1User()->id);
});

it('assigns created_by from factory when no user authenticated', function () {
    Auth::logout();

    $rule = YnhOsqueryRule::factory()->create();

    expect($rule->created_by)->not->toBeNull();
});
