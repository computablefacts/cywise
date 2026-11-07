# ITEM-012: Persistent translation table for ChunkAssistant

- Owner: Junie
- Base: main
- Branch: item/ITEM-012
- Status: ready_to_merge

## Description

ChunkAssistant::translate uses the cache to store translations, which leads to loss of translations during deployments. 
The goal is to use a persistent database table instead of Cache::remember.

## Acceptance

- [x] A migration creates a `translations` table with columns: `from_lang`, `to_lang`, `source_text_hash`, `source_text`, `translated_text`.
- [x] A `Translation` model is created.
- [x] `ChunkAssistant::translate` uses this table to store and retrieve translations.
- [x] Passing unit tests verify that translations are persisted in the database and reused.
