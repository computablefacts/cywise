# ITEM-014 Specs

## ITEM-014-S01: Use Http::fake instead of Mockery overload

Status: completed
Requirement: Replace the unstable Mockery `overload:` in `ChunkAssistantTranslationTest` with Laravel's `Http::fake()`.
Test: `php vendor/bin/phpunit tests/Feature/AgentSquad/Assistants/TextAssistantTest.php tests/Unit/WithDb/ChunkAssistantTranslationTest.php` passes.
Commit: 6510afdd
