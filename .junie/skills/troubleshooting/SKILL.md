---
name: troubleshooting
description: Common runtime, testing, and asset pipeline issues for the Towerify project.
---

# Troubleshooting

Use this skill when something fails unexpectedly during local development or test execution.

## Common Issues

- “Environment is not testing. I quit”  
  `tests/TestCaseWithDb.php` enforces `APP_ENV=testing` to protect data. Ensure you run via PHPUnit or set the env accordingly.
- Missing DB during tests  
  Either skip DB tests or configure `DB_*` envs per `phpunit.xml`.
- Tailwind classes not applied  
  Ensure `npm run dev` or `npm run build` has run and that Vite is serving the correct assets.

## Related Guidance

- Check `testing` for PHPUnit and database setup details.
- Check `build-and-configuration` for asset pipeline and project structure notes.