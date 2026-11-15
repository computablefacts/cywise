---
title: Decompose
description: Decompose complex tasks into sub-items.
---

# Skill: Decompose

Decompose complex tasks into sub-items.

## Process

1. List all Items in `todo` status.
2. Analyze all listed Items.
3. Automatically set simple Items to `ready` status.
4. Tell the user which Items are now `ready`.
5. Show the user the Items that require decomposition.
6. Ask the user to pick an Item to decompose.
7. Propose sub-item Issues for technical sub-tasks for complex Items.
8. Seek user feedback and validation for the proposed decomposition.
9. Update the proposal based on user feedback.
10. Create all sub-item Issues at once in `ready` status after user validation.
11. Link sub-items to the main Issue (e.g., "Part of #ID").
12. Include the following in each sub-item:
    - **Requirement**: Technical Gherkin "Rule" or "Scenario".
    - **Test**: Description of the required passing unit tests.

## When to Decompose

- An Item has more than three acceptance criteria.
- The task affects multiple architectural layers.
- Multiple agents could work in parallel.
- Technical risks require isolated validation.

## Rules

GitHub Issues are the sole source of truth.

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

## Example

```markdown
Part of #ID.

## Requirement

Scenario: Credential validation logic
  Given a login request
  When the password hash does not match the stored hash
  Then the system must return a failure status
  And the account existence must not be disclosed

## Test

`AuthServiceTest::test_login_validates_credentials`
```
