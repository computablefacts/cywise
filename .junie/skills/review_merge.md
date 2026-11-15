---
title: Review and Merge
description: Review completed tasks and merge branches after approval.
---

# Skill: Review and Merge

Review completed tasks and merge branches after approval.

## Process

1. List all Items in `review` status.
2. Exclude sub-items from this list.
3. Ask the user which `review` Item to review.
4. Switch to the branch for the selected Item: `git switch issue/<ID>`.
5. Check that all sub-items are in `review` status.
6. Summarize the work performed.
7. Seek user approval for the changes.
8. If the user identifies missing items:
    a. Create new sub-items if necessary.
    b. Restart the development process.
9. When the user approves the changes:
    a. Set the Item and all sub-items to `done` status.
    b. Merge the branch into the base branch: `git switch <base-branch> && git merge --no-ff issue/<ID>`.
    c. Close the GitHub Issues.
