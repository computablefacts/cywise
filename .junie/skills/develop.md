# Skill: Develop

Develop features and fix bugs.

## Process

1. List all Items in `ready` status.
2. Exclude sub-items from this list.
3. Ask the developer which `ready` Item to start.
4. Set the status of the Item to `in-progress`.
5. Create a branch: `git switch -c issue/<ID>`.
6. Synchronize with GitHub: `gh issue view <ID>`.
7. Check all sub-items.
8. Do not start implementation if any sub-item is `blocked`.
9. For each sub-item:
    a. Synchronize with GitHub: `gh issue view <ID>`.
    b. Implement changes. If implementation requires clarification:
       i. Stop implementation.
       ii. Set the sub-item status to `blocked`.
       iii. Ask the user for clarification.
       iv. Comment on the sub-item with an explanation of the issue and the user's decisions.
       v. Set the sub-item status to `ready` again and resume implementation.
    c. Ensure the sub-item has passing unit tests.
    d. Commit changes for the sub-item.
    e. Set the sub-item status to `review`.
10. Implement and run parent item acceptance criteria when all sub-items are in `review`.
11. Commit changes for the parent item once all tests pass.
12. Move the parent item to `review`.

## Coding Rules

- Extract meaningful or repeated literals into constants or enums. Always use constants for specification-defined values. Leave obvious one-offs inline.
- Prefer early returns, continues, and shallow nesting. Keep function names under 30 characters. Use enums, not boolean parameters.
- Always use `{}` for control blocks. Separate logical blocks with blank lines.
- Keep comments brief: explain what and why. Add examples or ASCII diagrams when useful. Do not comment on untouched code.
- Keep members private unless access is necessary. Get explicit user approval before changing `private` to `internal` or `public`.
- Hide low-level I/O behind driver or abstraction layers. Expose domain-level APIs. A layer may call only its immediate lower layer.
- Minimize changed lines. Do not modify unrelated code, comments, formatting, or behavior.

## Testing and Bug Fixes

- Every Gherkin scenario must have an automated test.
- For bugs: write a failing regression test first.
- Observe every test passing before closing an issue.

## Commits

Use `type(#ID): <title>\n\n<Imperative description>` for implementation commits.

- **ID**: Use the sub-item Issue ID if one exists for the change; otherwise, use the main item Issue ID.
- Types: `feat` (feature), `fix` (bug), `chore` (maintenance), `refactor` (restructuring).
- Commit each completed sub-item or item once tests pass.
- Keep the whole subject within 50 characters when possible; never exceed 72.
- Capitalize the description; use an imperative verb; omit the final period.
- Separate subject and body with one blank line.
- Explain what and why, not how.

## Example Commit

```text
feat(#456): Validate credentials

Validate credentials and reject invalid login attempts without exposing account existence.
```
