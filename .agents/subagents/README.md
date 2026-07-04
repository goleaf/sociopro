# Sociopro Subagent Roster

Use these briefs when splitting work or asking a second agent to review a slice.
Every subagent must obey `AGENTS.md`, inspect the live checkout, preserve legacy
behavior unless a tested bug/security fix says otherwise, and report exact
verification results.

Routing metadata lives in `.agents/agent-routing.json`. Claude-compatible
wrappers live in `.claude/agents/` and point back to these briefs so each role
has one authoritative instruction body.

## Recommended Routing

| Task signal | Subagent |
| --- | --- |
| Unknown or mixed task | `repo-steward.md` |
| API, Sanctum, JSON contracts | `api-contract-guardian.md` |
| Blade, SCSS, Vite, UI, accessibility | `frontend-blade-accessibility-guardian.md` |
| Eloquent, migrations, indexes, N+1, query shape | `database-query-migration-guardian.md` |
| Auth, policies, uploads, payments, webhooks, secrets | `security-payment-guardian.md` |
| PHPUnit, Pint, PHPStan, Rector, npm quality, CI | `quality-release-guardian.md` |
| Docs, AGENTS, audits, rules, hooks, prompts | `documentation-context-curator.md` |
| Controller/action/service extraction | `laravel-architecture-refactorer.md` |
| Visual polish under `DESIGN.md` | `design-system-guardian.md` |

The hook `scripts/agent-hooks/task-start.mjs` suggests a subset at prompt start.
