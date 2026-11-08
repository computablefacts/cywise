# ITEM-014: Fix ChunkAssistantTranslationTest Mockery error

- Owner: Junie
- Base: main
- Branch: item/ITEM-014
- Status: ready

## Description

Fix the `Mockery\Exception\RuntimeException: Could not load mock App\AgentSquad\Assistants\TextAssistant, class already exists` error in GitHub Actions.
This occurs because `overload:` is used on a class that might already be loaded by other tests.

## Acceptance

- [ ] `ChunkAssistantTranslationTest` passes even when run after other tests that load `TextAssistant`.
- [ ] No more `overload:` used for `TextAssistant`.
- [ ] Uses `Http::fake()` to intercept API calls instead of class overloading.
