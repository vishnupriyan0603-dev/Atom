# svgAtom — Strict AI Development Rules

These rules are mandatory for every AI agent, developer, and automated coding system working on this repository.

## 1. Never Overwrite Existing Work

* NEVER overwrite existing code, configuration, documentation, or functionality without first inspecting it.
* NEVER delete existing functionality just because a new implementation appears better.
* NEVER replace a file completely when a targeted modification is sufficient.
* Preserve existing behavior unless the task explicitly requires changing it.
* Before modifying an important component, understand its dependencies and current behavior.
* If existing code conflicts with the requested task, explain the conflict before making a destructive change.
* Do not silently remove features, APIs, database fields, tests, comments, or configuration.

## 2. Every Task Gets Its Own Branch

Before starting any implementation task:

1. Inspect the current repository state.
2. Ensure the working tree is understood.
3. Create a dedicated Git branch for the task.
4. Never implement unrelated tasks in the same branch.

Branch naming:

```text
feature/<task-name>
fix/<task-name>
refactor/<task-name>
docs/<task-name>
test/<task-name>
chore/<task-name>
```

Example:

```text
feature/atom-memory-system
feature/knowledge-retrieval
fix/vector-search-timeout
refactor/knowledge-storage
```

## 3. One Branch = One Logical Task

A branch must represent one clear objective.

Do not mix:

* New features
* Unrelated bug fixes
* Refactoring
* Dependency upgrades
* Formatting changes
* Documentation changes

unless they are directly required by the same task.

If another issue is discovered, create a separate task and branch.

## 4. Task Start Protocol

At the beginning of every task, the AI must determine:

* Task objective
* Expected behavior
* Files likely to change
* Existing implementation
* Dependencies
* Risks
* Tests required
* Acceptance criteria

Before coding, inspect the relevant code.

Do not immediately start writing code based only on the task description.

## 5. Change Before/After Analysis

Before changing a component:

```text
CURRENT
↓
UNDERSTAND
↓
PLAN
↓
MODIFY
↓
TEST
↓
VERIFY
```

The AI must be able to explain:

* What existed before?
* What is changing?
* Why is it changing?
* What could break?
* How will the change be verified?

## 6. No Blind Overwriting

The AI must never use an approach equivalent to:

```text
delete everything
↓
generate new implementation
```

unless the task explicitly requires a complete replacement.

Prefer:

```text
inspect
↓
preserve
↓
modify minimally
↓
test
```

## 7. Commit Discipline

Commits should represent meaningful checkpoints.

Use clear commit messages:

```text
feat(memory): add long-term knowledge storage
feat(rag): add knowledge retrieval pipeline
fix(memory): prevent duplicate knowledge records
test(memory): add retrieval integration tests
refactor(ai): isolate learning pipeline
docs(architecture): document knowledge flow
```

Avoid meaningless commits such as:

```text
update
changes
fix stuff
test
work
```

## 8. End-of-Task Verification

Before a branch is considered complete, the AI must verify:

### Code

* Build succeeds.
* Syntax checks pass.
* Static analysis passes where available.
* Existing functionality still works.
* New functionality works.

### Tests

Run the appropriate:

* Unit tests
* Integration tests
* Feature tests
* Regression tests
* API tests
* Database tests
* Security checks

Do not claim a task is complete without verifying its acceptance criteria.

## 9. Different Tasks Require Different Checks

Do NOT use one generic verification process for every task.

The AI must select checks based on the task type.

### Feature

Check:

* New functionality
* Existing functionality
* Integration
* Edge cases
* Performance where relevant

### Bug Fix

Check:

* Original failure
* Regression test
* Related functionality
* Edge cases

### Database Change

Check:

* Migration
* Rollback
* Existing data compatibility
* Query behavior
* Indexes
* Data integrity

### API Change

Check:

* Request validation
* Response format
* Authentication/authorization
* Error handling
* Backward compatibility

### AI/ML Change

Check:

* Accuracy
* Retrieval quality
* Hallucination rate
* Regression against previous behavior
* Evaluation dataset
* Token/cost impact
* Latency

### Security Change

Check:

* Authentication
* Authorization
* Input validation
* Injection risks
* Secrets
* Permissions
* Regression/security tests

### Refactoring

Check:

* Behavior remains unchanged
* Existing tests pass
* Public interfaces remain compatible unless intentionally changed
* Performance does not regress

## 10. End-of-Day Repository Check

At the end of each development day, perform a repository health check.

Check:

```text
Git status
↓
Branches
↓
Uncommitted changes
↓
Unpushed commits
↓
Open tasks
↓
Failed tests
↓
CI status
↓
Security warnings
↓
Dependency changes
↓
Documentation status
```

Produce an end-of-day report containing:

* Completed tasks
* In-progress tasks
* Blocked tasks
* Branches created
* Branches ready for review
* Tests passed
* Tests failed
* CI status
* Risks
* Technical debt discovered
* Next actions

## 11. Merge Rules

A branch cannot be merged simply because the code "looks correct."

Before merge:

```text
Task complete
↓
Acceptance criteria verified
↓
Tests passed
↓
CI passed
↓
Review completed
↓
No unintended changes
↓
Merge
```

Do not merge branches containing unrelated work.

## 12. Merge Conflict Rules

When resolving conflicts:

* Never automatically choose "ours" or "theirs" without understanding the changes.
* Inspect both versions.
* Preserve valid functionality from both sides.
* Re-run relevant tests after resolving conflicts.
* Explain significant conflict-resolution decisions.

## 13. Never Hide Failures

The AI must never:

* Ignore failing tests.
* Delete a failing test simply to make CI pass.
* Disable validation to bypass an error.
* Suppress warnings without understanding them.
* Claim success when verification failed.
* Hide broken functionality from the developer.

If verification fails, report:

```text
FAILED
Reason:
Impact:
Likely cause:
Recommended fix:
```

## 14. Protected Areas

The AI must treat these as high-risk:

* Authentication
* Authorization
* Secrets
* Production configuration
* Database migrations
* Data deletion
* Payment functionality
* Security controls
* CI/CD configuration
* Deployment configuration
* Core AI model configuration
* Memory/knowledge deletion
* User data

Changes to high-risk areas require explicit approval.

## 15. No Destructive Commands Without Approval

The AI must not perform destructive operations such as:

```text
git reset --hard
git clean -fd
git push --force
database DROP
mass deletion
production data modification
```

unless explicitly authorized.

## 16. Preserve User Control

The AI is an assistant, not the owner of the repository.

The AI may:

* Inspect
* Plan
* Implement
* Test
* Report
* Propose improvements

The AI must not independently decide to:

* Delete important functionality
* Rewrite architecture
* Change security policy
* Change production behavior
* Merge high-risk changes
* Delete user data
* Modify protected infrastructure

without appropriate authorization.

## 17. Task Completion Record

Every completed task should record:

```text
Task:
Branch:
Objective:
Files changed:
Implementation:
Tests:
Verification:
Known limitations:
Risks:
Commit:
CI:
Merge status:
```

## 18. Project-Level Task Management

Tasks should follow:

```text
Project
  ↓
Epic
  ↓
Task
  ↓
Branch
  ↓
Implementation
  ↓
Tests
  ↓
Review
  ↓
Merge
  ↓
Project status update
```

Every task must have a clear relationship to the larger project objective.

Do not create isolated work that has no project context.

## 19. AI Must Check Existing Work Before Starting

Before beginning a new task, inspect:

* Existing branches
* Open pull requests
* Recent commits
* Existing issues
* Current architecture
* Related implementations
* Existing tests
* Current documentation

The AI must avoid duplicating work that already exists.

## 20. Continuous Improvement

After completing a task, the AI should identify:

* What went well?
* What failed?
* What can be automated?
* What test is missing?
* What technical debt was discovered?
* What documentation should be updated?
* What architectural improvement should be considered?

However, improvements that are outside the current task must become separate tasks rather than being silently implemented.

## 21. Golden Rule

The most important rule:

> **Understand first. Change second. Verify third. Merge last.**

ATOM must prioritize repository safety, traceability, reproducibility, and correctness over speed.

No change is considered complete until it can be explained, tested, verified, and traced back to a specific task.
