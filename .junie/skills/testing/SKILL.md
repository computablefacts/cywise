---
name: testing
description: PHPUnit setup, running tests, database-backed tests, and lightweight test patterns for the Towerify project.
---

# Testing

Use this skill when adding, running, or debugging tests.

## Overview

- Test runner: PHPUnit 10 (configured by `phpunit.xml`).
- Suites: `tests/Unit` and `tests/Feature` with files ending in `*Test.php`.
- Bootstrap: `vendor/autoload.php` (no Laravel kernel boot in the default bootstrap).
- Test environment: `phpunit.xml` forces `APP_ENV=testing` and provides an application key. It also defines DB credentials for MySQL tests.

## Running Tests

- Run all tests: `vendor/bin/phpunit`
- Run a single test file: `vendor/bin/phpunit tests/Unit/SmokeTest.php`

## Database-Backed Tests

- Some unit tests rely on MySQL and `FastRefreshDatabase` in `tests/TestCaseWithDb.php`.
- To enable DB tests locally, provision a test database and user:
    - Create DB: `CREATE DATABASE tw_testdb DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
    - Create user: `CREATE USER 'tw_testuser'@'localhost' IDENTIFIED BY 'z0rglub';`
    - Grant rights: `GRANT ALL ON tw_testdb.* TO 'tw_testuser'@'localhost'; FLUSH PRIVILEGES;`
- Alternatively, point the `DB_*` envs in `phpunit.xml` to a containerized MySQL instance.

## Non-DB, Fast Tests

- Plain PHPUnit tests that do not boot Laravel or touch the DB are encouraged for pure logic and smoke checks.

## How to Add a Simple Test

1. Create a file under `tests/Unit`, for example `tests/Unit/SmokeTest.php`
2. Use this content:

```
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_truth(): void
    {
        $this->assertTrue(true);
    }
}
```

3. Run `vendor/bin/phpunit`
4. Confirm the test passes without requiring the Laravel application or a database.

## Notes

- If you need Laravel features such as routing, HTTP, or models, create tests that extend a base TestCase which boots the app.
- For DB usage, extend `Tests\TestCaseWithDb` and ensure the test DB is reachable.
- For no-DB but Laravel HTTP helpers, consider adding a proper `tests/TestCase.php` with `CreatesApplication` if needed.
