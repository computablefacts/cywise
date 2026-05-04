---
name: build-and-configuration
description: Project setup, build tooling, Laravel/Wave structure, navigation/page placement, API registration, and asset conventions.
---

# Build and Configuration

Use this skill for environment setup, asset builds, project structure, page placement, and configuration-related changes.

## Stack and Tooling

- Stack: Laravel (Wave starter kit), Volt for UI, Tailwind + Vite, MySQL, PHP 8.2+.
- Wave/Volt: Public and admin pages are Volt-powered and live under the Wave theme.
- Node tooling: Vite for asset build, Tailwind configured via `tailwind.config.js`.

## Local Environment Basics

- PHP dependencies: `composer install`
- Node dependencies: `npm ci` or `npm i`
- Environment file: copy `.env.example` to `.env`, then adjust values for mail, DB, and external services.
- App key: `php artisan key:generate` is not needed during PHPUnit runs because a testing key is already provided in `phpunit.xml`.
- Build assets:
    - Development: `npm run dev`
    - Hot reload: `npm run dev -- --watch`
    - Production: `npm run build`
- Start app: `php artisan serve` or your preferred container orchestration.
- Docker: a `compose.yaml` exists if you standardize on Docker Compose.

## Laravel/Wave Configuration Notes

- Theme location: `resources/themes/cywise`.
- Translations: update `lang/fr.json` when adding or modifying UI strings. Keep French in sync with English.

## Adding Navigation and Pages to the Website

- Add a new page (Laravel Folio): create a Blade view in `resources/themes/cywise/pages`.
- Modify the website header: edit `resources/themes/cywise/components/marketing/elements/header.blade.php`.
- Modify the website footer: edit `resources/themes/cywise/partials/footer.blade.php`.
- Modify the website homepage: edit `resources/themes/cywise/pages/index.blade.php`.
- Always use Tailwind classes for UI components. Only create new CSS classes if absolutely necessary.

## Adding Navigation and Pages to the Webapp

- Add a menu item: edit `resources/themes/cywise/components/app/sidebar.blade.php`.
- Add a new page using the iframe pattern:
    1. Create the Blade view in `resources/themes/cywise/iframes`
    2. Create a controller in `app/Http/Controllers/Iframes` that returns that Blade view
    3. Register a route in `routes/web.php` pointing to the controller
    4. Create a loader page in `resources/themes/cywise/pages` that embeds the route via an `<iframe>`
- Add a new JavaScript API call to a JSON-RPC endpoint: edit `resources/themes/cywise/iframes/_json-rpc.blade.php`.
- Add a new image to a page: copy the image to `public/cywise/img` and reference it in the Blade view.
- Add a new CSS class: edit `public/cywise/css/app.css`.
- Add a new JavaScript file: add new JS files to `public/cywise/js` and import them in the Blade view.
- Always use FastBootstrap for UI components. Only create new CSS classes if absolutely necessary.
- The custom BlockNoteJs component lives in `resources/js/block-note.jsx` and is imported in `resources/themes/cywise/iframes/cyberscribe.blade.php`.
- The `resources/views` directory mostly contains deprecated views.

## API (JSON-RPC 2.0)

- Procedures live in `app/Http/Procedures` and follow Sajya.
- When creating new procedures, ensure they are registered in `routes/api.php` and covered by tests.

## Filament Admin Theming

- Admin theme overrides live in `resources/css/filament/admin/theme.css`.
- This file imports the base Filament theme, overrides several classes, and is built via Tailwind/Vite.

## Adding a New JSON-RPC Procedure

1. Create a class in `app/Http/Procedures` extending `Sajya\Server\Procedure`.
2. Define methods with the `#[RpcMethod]` attribute.
3. Use `App\Http\Requests\JsonRpcRequest` for parameter validation and user access.
4. Register the procedure in `routes/api.php` under the appropriate RPC endpoint.
5. Add a test case in `tests/Feature` (or `tests/Unit` if logic is isolated).

## Related Skills

- See `testing` for test execution and database-backed test setup.
- See `troubleshooting` for common environment and asset issues.