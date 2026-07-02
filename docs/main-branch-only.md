# Main Branch Only Workflow

All work in this repository must happen on `main`.

## Rules

- Do not create feature, refactor, docs, fix, chore, release, or temporary branches.
- Do not push work to any branch except `main`.
- Do not open pull requests from repository branches for routine agent work.
- If a non-main branch exists, merge it into `main`, verify the merged result, push `main`, then delete the branch locally and remotely.
- If isolation is needed for investigation, use uncommitted local changes only and finish by committing directly on `main`.

## Agent Checklist

Before making changes:

1. Run `git branch --show-current`.
2. If the current branch is not `main`, switch to `main` before editing.
3. Run `git pull --ff-only origin main`.

Before finishing:

1. Commit only on `main`.
2. Run the required verification commands.
3. Push `main`.
4. Remove any local or remote branch created during the work.
