# Towerify — Development Guidelines

This document captures project-specific knowledge to onboard advanced contributors quickly and to prevent common
pitfalls.

## Build and Configuration

- Stack: Laravel (Wave starter kit (https://devdojo.com/wave)), Volt for UI, Tailwind + Vite, MySQL, PHP 8.2+.
- Wave/Volt: Public and admin pages are Volt-powered and live under the Wave theme; see structure below.
- Node tooling: Vite for asset build, Tailwind configured via `tailwind.config.js`.

Environment basics (local):

- PHP dependencies: `composer install`
- Node dependencies: `npm ci` (or `npm i`)
- Environment file: copy `.env.example` to `.env`, then adjust values (mail, DB, external services). For testing
  specifics, see the Testing section.
- App key: `php artisan key:generate` (not needed during PHPUnit runs because a testing key is already provided in
  `phpunit.xml`).
- Build assets (development): `npm run dev` (hot reload: `npm run dev -- --watch`), production: `npm run build`.
- Start app: `php artisan serve` or your preferred container orchestration. A `compose.yaml` exists if you standardize
  on Docker Compose.

Laravel/Wave configuration notes:

- Theme location: `resources/themes/cywise`.
- Translations: update `lang/fr.json` when adding/modifying UI strings. Keep French in sync with English.

Adding Navigation and Pages to the website:

- Add a new page (Laravel Folio): create a Blade view in `resources/themes/cywise/pages`.
- Modify the website header: edit `resources/themes/cywise/components/marketing/elements/header.blade.php`.
- Modify the website footer: edit `resources/themes/cywise/partials/footer.blade.php`.
- Modify the website homepage: edit `resources/themes/cywise/pages/index.blade.php`.
- Always use Tailwind classes for UI components. Only create new CSS classes if absolutely necessary.

Adding Navigation and Pages to the webapp:

- Add a menu item: edit `resources/themes/cywise/components/app/sidebar.blade.php`.
- Add a new page (iframe pattern used by the app):
    1) Create the Blade view in `resources/themes/cywise/iframes`.
    2) Create a controller in `app/Http/Controllers/Iframes` that returns that Blade view.
    3) Register a route in `routes/web.php` pointing to the controller.
    4) Create a loader page (Laravel Folio) in `resources/themes/cywise/pages` that embeds the route via an `<iframe>`.
- Add a new JavaScript API call to a JSON-RPC endpoint: edit `resources/themes/cywise/iframes/_json-rpc.blade.php`.
- Add a new image to a page: copy the image to `public/cywise/img` and reference it in the Blade view.
- Add a new CSS class: edit `public/cywise/css/app.css`.
- Add a new JavaScript file: add new js files to `public/cywise/js` and import them in the Blade view.
- Always use FastBootstrap (https://fastbootstrap.com/) for UI components. Only create new CSS classes if absolutely
  necessary.
- Our custom BlockNoteJs (https://www.blocknotejs.org/) component lives in `resources/js/block-note.jsx` and is imported
  in `resources/themes/cywise/iframes/cyberscribe.blade.php`.
- The `resources/views` directory mosty contains deprecated views.

API (JSON‑RPC 2.0):

- Procedures live in `app/Http/Procedures` and follow Sajya (https://sajya.github.io/). When creating new procedures,
  ensure they are registered in `routes/api.php` and covered by tests.

Filament Admin Theming:

- Admin theme overrides live in `resources/css/filament/admin/theme.css`. This file imports the base Filament theme,
  overrides several classes, and is built via Tailwind/Vite (see `@config 'tailwind.config.js';`).

## Testing

Overview:

- Test runner: PHPUnit 10 (configured by `phpunit.xml`).
- Suites: `tests/Unit` and `tests/Feature` with files ending in `*Test.php`.
- Bootstrap: `vendor/autoload.php` (no Laravel kernel boot in the default bootstrap).
- Test environment: `phpunit.xml` forces `APP_ENV=testing` and provides an application key. It also defines DB
  credentials for MySQL tests (see below).

Running tests:

- Composer script (if available) or direct: `vendor/bin/phpunit`
- Run a single test file: `vendor/bin/phpunit tests/Unit/SmokeTest.php`

Database‑backed tests:

- Some unit tests rely on MySQL and `FastRefreshDatabase` (see `tests/TestCaseWithDb.php`).
- To enable DB tests locally, provision a test database and user as documented inline in `tests/TestCaseWithDb.php`:
    - Create DB: `CREATE DATABASE tw_testdb DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
    - Create user: `CREATE USER 'tw_testuser'@'localhost' IDENTIFIED BY 'z0rglub';`
    - Grant rights: `GRANT ALL ON tw_testdb.* TO 'tw_testuser'@'localhost'; FLUSH PRIVILEGES;`
- Alternatively, point the `DB_*` envs in `phpunit.xml` to a containerized MySQL instance.

Non‑DB, fast tests:

- You can add plain PHPUnit tests that do not boot Laravel or touch the DB. This is recommended for pure logic and smoke
  checks to keep the suite fast.

How to add a new simple test (demonstrated and verified):

1) Create a test file under `tests/Unit`, for example `tests/Unit/SmokeTest.php` with the following content:

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

2) Run the tests: `vendor/bin/phpunit` (or target just that file).

3) Expectation: the test passes without requiring the Laravel application or a database.

Notes:

- If you need Laravel features (routing, HTTP, models), create tests that extend a base TestCase which boots the app.
  For DB usage, extend `Tests\TestCaseWithDb` and ensure the test DB is reachable. For no‑DB but Laravel HTTP helpers,
  consider adding a proper `tests/TestCase.php` with `CreatesApplication` if needed.

## Code Style and Conventions

- Follow the repository’s existing formatting. A Pint configuration (`pint.json`) is present; run `./vendor/bin/pint` to
  auto‑fix style.
- Tailwind classes and FastBootstrap are used for UI — remain consistent with existing utility classes.
- Blade views for Wave/Volt pages live under `resources/themes/cywise`. Keep components idiomatic and translations
  updated in `lang/fr.json`.
- When touching Filament admin styles, update `resources/css/filament/admin/theme.css` and rebuild assets via Vite.

## Troubleshooting

- “Environment is not testing. I quit” — `tests/TestCaseWithDb.php` enforces `APP_ENV=testing` to protect data. Ensure
  you run via PHPUnit or set the env accordingly.
- Missing DB during tests — either skip DB tests or configure `DB_*` envs per `phpunit.xml`.
- Tailwind classes not applied — ensure `npm run dev`/`build` has run and that Vite is serving the correct assets.
