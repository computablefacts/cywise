# Agent Workflow

All agents must follow `.craft-agents/DOCTRINE.md`.

## Files

### Doctrine — `.craft-agents/DOCTRINE.md`

One shared set of project rules for all agents.

### Item — `.craft-agents/items/ITEM-001.md`

The Item file defines a development task, its scope, acceptance criteria, ownership, and Git branch. It tracks the task's status from creation to merge.

```markdown
# ITEM-001: User login

- Owner: Claude
- Base: main
- Branch: item/ITEM-001
- Status: done

## Description

Implement email and password authentication.
Allow existing users to log in securely.
User registration is outside this Item's scope.

## Acceptance

- [x] Users can log in.
- [x] Invalid credentials are rejected.
- [x] Successful login creates a session.
```

Each Item has one active agent and one Git branch.

#### Item statuses

- backlog: The Item exists, but specification work has not started.
- specifying: Agents are defining or clarifying the Specs.
- ready: The user has approved all required Specs, and the Git branch can be created. Development can begin.
- in_progress: The Git branch has been created, and an agent is implementing approved Specs.
- blocked: An unresolved question or dependency prevents further work.
- review: Implementation is complete, and all Spec tests pass. Final verification is in progress.
- ready_to_merge: All required Specs are completed, acceptance tests pass, and the review is approved. The Item awaits a human merge.
- done: The merge into the base branch has been authorized.

Record every status change in Activity.

#### Git commands

##### Creating the item branch (specifying → ready)

After the user approves all required Specs, commit the Item and Spec files to the base branch.

```
git switch <base-branch>

git add .craft-agents/items/ITEM-NNN.md \
        .craft-agents/specs/ITEM-NNN.md \
        .craft-agents/activity/ITEM-NNN.md

git commit -m "chore(ITEM-NNN): Record approved specs for user login"

git switch -c item/ITEM-NNN
```

Replace <base-branch> with the Item's recorded base branch, such as main.

This initial commit records the approved requirements. It is not an implementation commit.

Commit all implementation changes to the Item branch.

##### Merge branch (ready_to_merge → done)

Only a human may authorize the merge. Agents must not merge without explicit authorization.

```
git switch <base-branch> && git merge --no-ff item/ITEM-NNN
```

Set done only after the merge succeeds. Record the merge and final status in Activity.

Commit post-merge record updates to the base branch when needed.

### Spec — `.craft-agents/specs/ITEM-001.md`

The Spec file defines the testable requirements of an Item, their approval status, and expected tests. It tracks each requirement from approval to implementation, with a linked Git commit.

```markdown
# ITEM-001 Specs

## ITEM-001-S01: Validate credentials

Status: completed
Requirement: Validate credentials and reject invalid login attempts without exposing account existence.
Test: Unit tests for valid and invalid credentials.
Commit: a31f2c1

## ITEM-001-S02: Create session

Status: completed
Requirement: Create a session after successful login.
Test: Unit test for session creation.
Commit: b85d912

## ITEM-001-S03: Handle errors

Status: dropped
Requirement: Handle authentication errors.
Test: Unit test for error responses.
Commit: -
```

#### Spec statuses

- dropped: Excluded from implementation.
- pending: Awaiting user approval.
- approved: Approved by the user for implementation.
- in_progress: Implementation in progress.
- completed: Implementation committed and tests passed.

Only the user can approve Specs.

Agents must implement only approved Specs. Each completed Spec requires passing tests and a dedicated commit.

Record every status change in Activity.

### Activity — `.craft-agents/activity/ITEM-001.md`

The Activity file records all actions and interactions between agents and the user in chronological order. It tracks progress, questions, decisions, approvals, tests, commits, and merges.

The following example covers the full development of ITEM-001.

```markdown
# ITEM-001 Activity

- 2026-10-25T09:00Z | Claude | I001 | created | User login
- 2026-10-25T09:02Z | Claude | I001 | updated | backlog → specifying
- 2026-10-25T09:03Z | Claude | I001-S01 | created | Validate credentials; pending
- 2026-10-25T09:04Z | Claude | I001-S02 | created | Create session; pending
- 2026-10-25T09:05Z | Claude | I001-S03 | created | Handle errors; pending
- 2026-10-25T09:10Z | Claude | question | asked | Should login errors reveal account existence?
- 2026-10-25T09:12Z | User | decision | provided | Use generic errors
- 2026-10-25T09:13Z | Claude | I001-S01 | updated | Include generic errors
- 2026-10-25T09:14Z | Claude | I001-S03 | updated | pending → dropped (covered by S01)
- 2026-10-25T09:15Z | Claude | question | asked | Do you approve S01?
- 2026-10-25T09:16Z | User | decision | provided | yes
- 2026-10-25T09:17Z | Claude | I001-S01 | updated | pending → approved
- 2026-10-25T09:19Z | Claude | question | asked | Do you approve S02?
- 2026-10-25T09:20Z | User | decision | provided | yes
- 2026-10-25T09:21Z | Claude | I001-S02 | updated | pending → approved
- 2026-10-25T09:22Z | Claude | I001 | updated | specifying → ready
- 2026-10-25T09:23Z | Claude | git | commit | e93a012: record approved Specs on main
- 2026-10-25T09:24Z | Claude | git | checkout | Create branch item/ITEM-001 from main
- 2026-10-25T09:25Z | Claude | I001 | updated | ready → in_progress
- 2026-10-25T09:26Z | Claude | I001-S01 | updated | approved → in_progress
- 2026-10-25T09:30Z | Claude | I001 | updated | in_progress → blocked
- 2026-10-25T09:31Z | Claude | question | asked | Identity provider unavailable. Use local mock?
- 2026-10-25T09:35Z | User | decision | provided | yes
- 2026-10-25T09:36Z | Claude | I001 | updated | blocked → in_progress
- 2026-10-25T09:46Z | Claude | I001-S01 | updated | in_progress → completed
- 2026-10-25T09:47Z | Claude | git | commit | a31f2c1: record Spec S01 on item/ITEM-001
- 2026-10-25T09:50Z | Claude | I001-S02 | updated | approved → in_progress
- 2026-10-25T10:03Z | Claude | I001-S02 | updated | in_progress → completed
- 2026-10-25T10:05Z | Claude | git | commit | b85d912: record Spec S02 on item/ITEM-001
- 2026-10-25T10:10Z | Claude | I001 | updated | in_progress → review
- 2026-10-25T10:37Z | Claude | I001 | updated | review → ready_to_merge
- 2026-10-25T10:38Z | Claude | question | asked | Authorize merge of item/ITEM-001 into main?
- 2026-10-25T10:40Z | User | decision | provided | Authorize merge into main
- 2026-10-25T10:44Z | Claude | I001 | updated | ready_to_merge → done
- 2026-10-25T10:45Z | Claude | git | commit | e93a012: record approved merge on item/ITEM-001
- 2026-10-25T10:46Z | Claude | git | merge | Merge branch item/ITEM-001 into main
```

Use full Spec IDs when the reference could be ambiguous.

Keep Activity chronological and append-only. Never change or delete previous entries.

Record actual actions only. Never invent approvals, results, or commits.

## Workflow

1. `backlog` → `specifying`: Define the Specs and tests.
2. `specifying` → `ready`: Obtain user approval for all required Specs.
3. `ready` → `in_progress`: Commit approved requirements to the base branch. Create the Item branch.
4. For each Spec, follow `pending` → `approved` → `in_progress` → `completed`. Require passing tests and a dedicated commit before completion.
5. `in_progress` → `review`: Complete all required Specs and pass their tests.
6. `review` → `ready_to_merge`: Pass acceptance tests and obtain review approval. Return to `in_progress` if changes are necessary.
7. `ready_to_merge` → `done`: Obtain explicit human authorization. Merge successfully before setting `done`.

Use `blocked` when work cannot continue. Record the cause and resume the previous status after resolution.

Return changed requirements to `pending` for user approval. Mark excluded Specs as `dropped`.

Record every transition in Activity after its required actions occur.