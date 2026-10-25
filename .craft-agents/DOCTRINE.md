# Doctrine

## Communication

Apply ASD-STE100 Simplified Technical English writing principles to **all languages** and all human-facing text, including comments, reviews, replies, and commits:

- Use the active voice.
- Use 20 words or fewer per sentence.
- Express one idea per sentence.
- Prefer simple present, past, and future tenses.
- Use simple, consistent terms. Avoid idioms, slang, and unnecessary jargon.
- Keep paragraphs to six sentences or fewer.
- Use as few words as possible without losing necessary information.
- Avoid superlatives, praise, and unnecessary agreement.
- State facts directly.

For languages other than English, apply equivalent clarity rules.

## Code

- Extract meaningful or repeated literals into constants or enums. Always use constants for specification-defined values. Leave obvious one-offs inline.
- Prefer early returns, continues, and shallow nesting. Keep function names under 30 characters. Use enums, not boolean parameters.
- Always use `{}` for control blocks. Separate logical blocks with blank lines.
- Keep comments brief: explain what and why. Add examples or ASCII diagrams when useful. Do not comment on untouched code.
- Keep members private unless access is necessary. Get explicit user approval before changing `private` to `internal` or `public`.
- Hide low-level I/O behind driver or abstraction layers. Expose domain-level APIs. A layer may call only its immediate lower layer.
- Minimize changed lines. Do not modify unrelated code, comments, formatting, or behavior.

## Bug Fixes

Write a failing regression test, observe the failure, implement the fix, then observe the test passing.

## Commits

Use `<type>(ITEM-NNN)(SPEC-NN): <Imperative description>` on the assigned Item branch.

Types: `feat` (feature), `fix` (bug), `chore` (maintenance), `refactor` (behavior-preserving restructuring).

- Reference an existing Item and approved Spec in the body. Each completed Spec needs at least one related commit.
- Keep the whole subject within 50 characters when possible; never exceed 72.
- Capitalize the description; use an imperative verb; omit the final period.
- Separate subject and body with one blank line.
- Explain what and why, not how.

Example:

```text
feat(ITEM-001)(SPEC-01): Validate credentials

Validate credentials and reject invalid login attempts without exposing account existence.
```

## Enforcement

Agents must report Doctrine violations before accepting work. Only the user may approve Doctrine changes.