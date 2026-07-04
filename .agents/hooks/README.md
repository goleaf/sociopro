# Sociopro Agent Hooks

These project-local hooks turn the repository standards into fast agent feedback.
They are intentionally conservative: they inspect, remind, and optionally block,
but they do not rewrite application code, stage unrelated files, or push without
verification.

Shared routing and enforcement rules live in `.agents/agent-routing.json`.

## Installed Hooks

| Hook | Event | Script | Purpose |
| --- | --- | --- | --- |
| Pre-tool safety guard | `PreToolUse` on shell commands | `scripts/agent-hooks/pre-tool-guard.mjs` | Blocks broad staging, hard resets, destructive cleans, force pushes, broad restores, and non-test destructive migrations. |
| Task start context | `SessionStart`, `UserPromptSubmit` | `scripts/agent-hooks/task-start.mjs` | Injects current stack, dirty-tree state, source-of-truth docs, and suggested subagents. |
| Post-edit guard | `PostToolUse` on edits | `scripts/agent-hooks/post-edit-guard.mjs` | Scans added diff lines for debug code, raw DB calls, Blade queries, unsafe `env()`, `$request->all()`, `$guarded = []`, and doc drift. |
| Guarded publish | `Stop` | `scripts/agent-hooks/guarded-publish.mjs` | Reminds the agent to verify, stage only intended files, commit, and push. Can block dirty final states when strict mode is enabled. |
| Impeccable UI detector | `PostToolUse` on edits | `.agents/skills/impeccable/scripts/hook.mjs` | Existing design/UI detector for UI-relevant file edits. |
| Git pre-commit guard | Git `pre-commit` | `.githooks/pre-commit` | Blocks staged protected paths, secret-like values, debug code, raw DB APIs, Blade query hotspots, stale-doc wording, and missing docs for behavior/tooling slices. |
| Git commit message guard | Git `commit-msg` | `.githooks/commit-msg` | Enforces Conventional Commit subjects and blocks agent-internal commit wording. |

Enable the Git hooks in this checkout:

```bash
git config core.hooksPath .githooks
```

## Strict Publication Mode

By default, `guarded-publish.mjs` is advisory because this checkout may already
contain unrelated user changes before a task starts. To make dirty end states
block hook completion:

```bash
SOCIOPRO_HOOK_ENFORCE_PUBLICATION=1
```

The strict mode still does not blindly commit or push. It forces the agent to
run the relevant checks, inspect the diff, commit only the intended slice, and
push only after the intended commit is at `HEAD`.

## Why Not Blind Auto-Commit?

The project rules require:

- inspect `git status` before staging;
- stage only task-owned files;
- scan staged diffs for secrets;
- run verification before commit;
- push only after the intended commit is at `HEAD`.

A hook that auto-commits every prompt would eventually commit user work, broken
tests, generated caches, or secrets. This hook set enforces the publication
discipline without bypassing the safety checks.

## Manual Smoke Tests

```bash
node --check scripts/agent-hooks/task-start.mjs
node --check scripts/agent-hooks/pre-tool-guard.mjs
node --check scripts/agent-hooks/post-edit-guard.mjs
node --check scripts/agent-hooks/guarded-publish.mjs
node --check scripts/agent-hooks/staged-diff-guard.mjs
node --check scripts/agent-hooks/commit-msg-guard.mjs
npm run agent:hooks:smoke
printf '{}' | node scripts/agent-hooks/task-start.mjs
printf '{"tool_input":{"command":"git add ."}}' | node scripts/agent-hooks/pre-tool-guard.mjs
printf '{}' | node scripts/agent-hooks/post-edit-guard.mjs
printf '{}' | node scripts/agent-hooks/guarded-publish.mjs
```
