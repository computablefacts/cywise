# Agent Workflow

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

## Rules

- GitHub Issues are the sole source of truth.
- An Item is a main Issue. A sub-item is a child Issue linked to an Item.
- Item statuses are GitHub labels. The status flow is: `specifying` → `todo` → `ready` → `in-progress` → `review` → `done`. An Item or a sub-item can hold the temporary status `blocked`.
- Run one process at a time. Each process contains user validation steps. Continue to the next applicable process only after the current process completes.
- Never implement an Item that is not in `ready` status. Never merge without user approval.

## Select the Process

Before you act, apply the first rule that matches the situation, then run that process:
1. The user requests a new feature or a bug fix, and no open Item covers it. Run Define. 
2. One or more Items are in `todo` status. Run Decompose. 
3. One or more Items are in `ready` status. Run Develop. 
4. One or more Items are in `review` status. Run Review and Merge. 
5. No rule matches. Show the open Items with their statuses. Ask the user what to do. 

If several rules match, show the matching Items and ask the user which one to work on.

## Define: Create Requirements

### Process

1. Review the user's request. 
2. Create the Item: `gh issue create --title "<title>" --body-file <file>`
3. Set the status to `specifying`: `gh issue edit <ID> --add-label "specifying"`
4. Structure the Item body:
   - **Description:** Explain the goal and scope in detail. 
   - **Acceptance Criteria:** Use Gherkin scenarios to define testable conditions.
5. Seek user validation. 
6. Update the Item based on user feedback: `gh issue edit <ID> --body-file <file>`
7. Set the status to `todo` once the user validates the Item: `gh issue edit <ID> --remove-label "specifying" --add-label "todo"`

### Example Item

```markdown
## Description

Implement email and password authentication to allow existing users to log in securely.

## Acceptance Criteria

**Scenario:** Successful login
**Given** a registered user with email "user@example.com" and password "password123"
**When** the user attempts to log in with these credentials
**Then** a session is created
**And** the user is redirected to the dashboard

**Scenario:** Failed login
**Given** a registered user with email "user@example.com"
**When** the user attempts to log in with an incorrect password
**Then** the system displays an error message
**And** the user remains on the login page

**Scenario:** Unregistered user
**Given** an unregistered user with email "stranger@example.com"
**When** the user attempts to log in
**Then** the system displays an error message
**And** the user remains on the login page
```

## Decompose: Break Items into Sub-items

### Process

1. List all Items in `todo` status: `gh issue list --label "todo"`
2. Analyze all listed Items. 
3. Set simple Items directly to `ready` status: `gh issue edit <ID> --remove-label "todo" --add-label "ready"`
4. Tell the user which Items are now `ready`. 
5. Show the user the Items that require decomposition. 
6. Ask the user to pick an Item to decompose. 
7. Propose sub-items for technical sub-tasks. 
8. Seek user feedback and validation. Update the proposal based on feedback. 
9. Create all sub-item at once in `ready` status after user validation: `gh issue create --title "<title>" --body-file <file> --label "ready"`
10. Link each sub-item to the main Item: `gh issue edit <sub-ID> --add-parent <main-ID>`

### When to Decompose

Decompose an Item when one or more of these conditions apply:
- The Item has more than three acceptance criteria. 
- The task affects multiple architectural layers. 
- Multiple agents could work in parallel. 
- Technical risks require isolated validation.

### Example Sub-item

Each sub-item contains a Requirement (technical Gherkin "Rule" or "Scenario") and a Test (description of the required passing unit tests).
```markdown
Part of #ID.

## Requirement

**Scenario:** Credential validation logic
  **Given** a login request
  **When** the password hash does not match the stored hash
  **Then** the system must return a failure status
  **And** the account existence must not be disclosed

## Test

`AuthServiceTest::test_login_validates_credentials`
```

## Develop: Implement Items

### Process

1. List all Items in `ready` status: `gh issue list --label "ready"`. Exclude sub-items from this list. 
2. Ask the user which `ready` Item to start. 
3. Set the Item status to `in-progress`: `gh issue edit <ID> --remove-label "ready" --add-label "in-progress"`
4. Create a branch: `git switch -c issue/<ID>`
5. Synchronize with GitHub: `gh issue view <main-ID>`. Check all sub-items. 
6. Do not start implementation if any sub-item is `blocked`. 
7. For each sub-item:
   1. Synchronize with GitHub: `gh issue view <sub-ID>`
   2. Implement the changes. If the implementation requires clarification:
      1. Stop the implementation. 
      2. Set the sub-item status to `blocked`: `gh issue edit <sub-ID> --remove-label "ready" --add-label "blocked"`
      3. Ask the user for clarification.
      4. Comment on the sub-item with the issue and the user's decisions: `gh issue comment <sub-ID> --body "<explanation>"`
      5. Set the sub-item status to ready again: `gh issue edit <sub-ID> --remove-label "blocked" --add-label "ready"`
      6. Resume the implementation.
8. Ensure the sub-item has passing unit tests. 
9. Commit the changes for the sub-item. 
10. Set the sub-item status to review: `gh issue edit <sub-ID> --remove-label "ready" --add-label "review"`
11. Implement and run the parent Item acceptance criteria when all sub-items are in `review`. 
12. Commit the changes for the parent Item once all tests pass. 
13. Move the parent Item to review: `gh issue edit <main-ID> --remove-label "in-progress" --add-label "review"`

### Coding Rules

- Extract meaningful or repeated literals into constants or enums. Always use constants for specification-defined values. Leave obvious one-offs inline.
- Prefer early returns, continues, and shallow nesting. Keep function names under 30 characters. Use enums, not boolean parameters.
- Always use {} for control blocks. Separate logical blocks with blank lines.
- Keep comments brief: explain what and why. Add examples or ASCII diagrams when useful. Do not comment on untouched code.
- Keep members private unless access is necessary. Get explicit user approval before changing private to internal or public.
- Hide low-level I/O behind driver or abstraction layers. Expose domain-level APIs. A layer may call only its immediate lower layer.
- Minimize changed lines. Do not modify unrelated code, comments, formatting, or behavior.

### Testing and Bug Fixes

- Every Gherkin scenario must have an automated test.
- For bugs: write a failing regression test first.
- Observe every test passing before closing an issue.

### Commits

Use `type(#ID): <title>` for the commit subject, one blank line, then an imperative description.
- **ID:** Use the sub-item Issue ID if one exists for the change. Otherwise use the main Item Issue ID. 
- **Types:** `feat` (feature), `fix` (bug), `chore` (maintenance), `refactor` (restructuring). 
- Commit each completed sub-item or Item once its tests pass. 
- Keep the whole subject within 50 characters when possible. Never exceed 72. 
- Capitalize the description. Use an imperative verb. Omit the final period. 
- Explain what and why, not how.

### Example Commit

```markdown
feat(#456): Validate credentials

Validate credentials and reject invalid login attempts without exposing account existence.
```

## Review and Merge

### Process

1. List all Items in review status: `gh issue list --label "review"`. Exclude sub-items from this list. 
2. Ask the user which `review` Item to review. 
3. Switch to the branch of the selected Item: `git switch issue/<main-ID>`
4. Synchronize with GitHub: `gh issue view <main-ID>`
5. Check that all sub-items are in `review` status. 
6. Summarize the work performed. 
7. Seek user approval for the changes. 
8. If the user identifies missing items:
   1. Create new sub-items if necessary. 
   2. Run Develop again for these sub-items.
9. When the user approves the changes:
   1. Set the Item and all sub-items to `done` status: `gh issue edit <ID> --remove-label "review" --add-label "done"`
   2. Merge the branch into the base branch: `git switch <base-branch> && git merge --no-ff issue/<main-ID>`
   3. Close the GitHub Issues: `gh issue close <ID>`