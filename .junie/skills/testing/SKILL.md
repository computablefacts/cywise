---
name: testing
description: PHPUnit and Pest setup, running tests, database-backed tests, and lightweight test patterns for the Towerify project.
---

# Testing

Use this skill when adding, running, or debugging tests. Towerify uses both PHPUnit and Pest.

## Overview

- Test runners: Pest and PHPUnit 10 (configured by `phpunit.xml`).
- Suites: `tests/Unit` and `tests/Feature`.
- Bootstrap: `vendor/autoload.php` (no Laravel kernel boot in the default bootstrap).
- Test environment: `phpunit.xml` forces `APP_ENV=testing` and provides an application key. It also defines DB credentials for MySQL tests.
- Configuration: `tests/Pest.php` configures Pest behaviors and base classes for different directories.
- Mocking: Use `Illuminate\Support\Facades\Http::fake()` for API mocks and `Illuminate\Support\Facades\Config::set()` to override settings during tests.

## Running Tests

- Run all tests (via Pest): `vendor/bin/pest`
- Run all tests (via PHPUnit): `vendor/bin/phpunit`
- Run a single test file: `vendor/bin/pest tests/Unit/SmokeTest.php`
- Run with coverage: `vendor/bin/pest --coverage`

## Database-Backed Tests

- Some unit tests rely on MySQL and `FastRefreshDatabase` in `tests/TestCaseWithDb.php`.
- To enable DB tests locally, provision a test database and user:
    - Create DB: `CREATE DATABASE tw_testdb DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
    - Create user: `CREATE USER 'tw_testuser'@'localhost' IDENTIFIED BY 'z0rglub';`
    - Grant rights: `GRANT ALL ON tw_testdb.* TO 'tw_testuser'@'localhost'; FLUSH PRIVILEGES;`
- Alternatively, point the `DB_*` envs in `phpunit.xml` to a containerized MySQL instance.

## Non-DB, Fast Tests

- Plain PHPUnit tests that do not boot Laravel or touch the DB are encouraged for pure logic and smoke checks.

## How to Add a Simple PHPUnit Test

1. Create a file under `tests/Unit`, for example `tests/Unit/PhpUnitSmokeTest.php`
2. Use this content:

```
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhpUnitSmokeTest extends TestCase
{
    public function test_truth(): void
    {
        $this->assertTrue(true);
    }
}
```

3. Run `vendor/bin/phpunit tests/Unit/PhpUnitSmokeTest.php`
4. Confirm the test passes without requiring the Laravel application or a database.

## How to Add a Simple Pest Test

1. Create a file under `tests/Unit`, for example `tests/Unit/PestSmokeTest.php`
2. Use this content:

```php
<?php

it('asserts that true is true', function () {
    expect(true)->toBeTrue();
});

test('truth', function () {
    $this->assertTrue(true);
});
```

3. Run `vendor/bin/pest tests/Unit/PestSmokeTest.php`
4. Confirm the test passes.

## Mocking and Fakes

When testing components that interact with external services or configuration, use Laravel's built-in mocking:

- **HTTP Fakes**:
  ```php
  use Illuminate\Support\Facades\Http;

  Http::fake([
      'api.example.com/*' => Http::response(['data' => 'mocked'], 200),
  ]);

  // Execute code...

  Http::assertSent(fn ($request) => str_contains($request->url(), 'api.example.com'));
  ```

- **Config Overrides**:
  ```php
  use Illuminate\Support\Facades\Config;

  Config::set('services.scrapfly.key', 'test_key');
  ```

## Notes

- If you need Laravel features such as routing, HTTP, or models, create tests that extend a base TestCase which boots the app.
- For DB usage, extend `Tests\TestCaseWithDb` and ensure the test DB is reachable.
- For no-DB but Laravel HTTP helpers, consider adding a proper `tests/TestCase.php` with `CreatesApplication` if needed.
