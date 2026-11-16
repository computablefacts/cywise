---
title: Define
description: Define requirements and acceptance criteria for a task.
---

# Skill: Define

Define requirements and acceptance criteria for a task.

## Process

1. Review the user's request.
2. Create a main GitHub Issue (Item): `gh issue create --title "<title>" --body-file <file>`.
3. Set the status to `specifying`: `gh issue edit <ID> --add-label "specifying"`.
4. Follow the required structure for the Item:
    - **Description**: Explain the goal and scope in detail.
    - **Acceptance Criteria**: Use Gherkin scenarios to define testable conditions.
5. Seek user validation.
6. Update the Item based on user feedback: `gh issue edit <ID> --body-file <file>`.
7. Set the status to `todo` once the user validates the Item: `gh issue edit <ID> --remove-label "specifying" --add-label "todo"`.

## Example

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
