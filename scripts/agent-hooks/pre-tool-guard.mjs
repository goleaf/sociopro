import { hookContext, readHookPayload } from './hook-lib.mjs';

const payload = await readHookPayload();
const command = [
    payload.command,
    payload.cmd,
    payload.tool_input?.command,
    payload.tool_input?.cmd,
    payload.toolInput?.command,
    payload.toolInput?.cmd,
].filter(Boolean).join('\n').trim();

if (!command) {
    process.exit(0);
}

const blockers = [
    {
        pattern: /\bgit\s+reset\s+--hard\b/,
        reason: 'git reset --hard can destroy user work. Ask explicitly before using it.',
    },
    {
        pattern: /\bgit\s+clean\s+-[^\n]*f/,
        reason: 'git clean -f can delete untracked user files. Ask explicitly before using it.',
    },
    {
        pattern: /\bgit\s+(checkout|restore)\s+(\.|\*|:\/)\b/,
        reason: 'broad checkout/restore can overwrite unrelated work. Use explicit pathspecs only after review.',
    },
    {
        pattern: /\bgit\s+branch\s+-D\b/,
        reason: 'force-deleting branches needs explicit confirmation.',
    },
    {
        pattern: /\bgit\s+push\b[^\n]*(--force|-f|--mirror)/,
        reason: 'force push or mirror push is forbidden without explicit approval.',
    },
    {
        pattern: /\bgit\s+add\s+(\.|-A|--all)(?:\s|$)/,
        reason: 'broad staging is unsafe in this repo. Stage explicit task-owned paths.',
    },
    {
        pattern: /\bgit\s+commit\s+-am\b/,
        reason: 'commit -am bypasses staged-diff review and may include unrelated tracked files.',
    },
    {
        pattern: /\bphp\s+artisan\s+(migrate:fresh|migrate:reset|db:wipe)\b(?![^\n]*(APP_ENV=testing|--env=testing|DB_DATABASE=.*(:memory:|tmp|temporary)))/,
        reason: 'destructive database commands must target a test/temporary database or have explicit approval.',
    },
    {
        pattern: /\brm\s+-rf\s+(\.|\/|\*)\b/,
        reason: 'broad rm -rf is too destructive for agent automation.',
    },
];

const hit = blockers.find((blocker) => blocker.pattern.test(command));

if (!hit) {
    process.exit(0);
}

const context = [
    'Sociopro pre-tool guard blocked a risky command.',
    `Reason: ${hit.reason}`,
    `Command: ${command.slice(0, 500)}`,
    'Use a narrower command, explicit pathspecs, a test-only database target, or ask the user for approval when destructive action is truly required.',
].join('\n');

console.error(context);
console.log(hookContext(context, 'PreToolUse'));
process.exit(2);
