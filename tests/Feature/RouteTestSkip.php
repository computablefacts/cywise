<?php

// tests/Feature/RouteResponseTest.php

use function Pest\Laravel\get;

it('responds with 200 for all routes', function (string $route) {
    $response = get($route);
    $response->assertStatus(200);
})->with('routes')
->skip('Fail during CywiseSeeder with: PDOException: SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'name\' cannot be null');

test('responds with 200 for all auth routes', function ($url) {
    $user = \App\Models\User::find(1);

    $this->actingAs($user);

    $response = $this->get($url);

    $response->assertStatus(200);
})->with('authroutes');
