import { execFileSync, spawnSync } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const scripts = [
    'scripts/agent-hooks/hook-lib.mjs',
    'scripts/agent-hooks/task-start.mjs',
    'scripts/agent-hooks/pre-tool-guard.mjs',
    'scripts/agent-hooks/post-edit-guard.mjs',
    'scripts/agent-hooks/guarded-publish.mjs',
    'scripts/agent-hooks/staged-diff-guard.mjs',
    'scripts/agent-hooks/commit-msg-guard.mjs',
    'scripts/agent-hooks/smoke.mjs',
];

for (const script of scripts) {
    execFileSync('node', ['--check', script], { stdio: 'inherit' });
}

execFileSync('node', ['-e', 'JSON.parse(require("fs").readFileSync(".codex/hooks.json", "utf8")); JSON.parse(require("fs").readFileSync(".agents/agent-routing.json", "utf8"));'], { stdio: 'inherit' });

const blockedStaging = spawnSync('node', ['scripts/agent-hooks/pre-tool-guard.mjs'], {
    input: JSON.stringify({ tool_input: { command: 'git add .' } }),
    encoding: 'utf8',
});

if (blockedStaging.status !== 2) {
    throw new Error('pre-tool guard did not block broad git add');
}

const allowedPush = spawnSync('node', ['scripts/agent-hooks/pre-tool-guard.mjs'], {
    input: JSON.stringify({ tool_input: { command: 'git push origin main' } }),
    encoding: 'utf8',
});

if (allowedPush.status !== 0) {
    throw new Error('pre-tool guard blocked normal git push');
}

execFileSync('node', ['scripts/agent-hooks/task-start.mjs'], { input: '{}', stdio: ['pipe', 'ignore', 'inherit'] });
execFileSync('node', ['scripts/agent-hooks/post-edit-guard.mjs'], { input: '{}', stdio: ['pipe', 'ignore', 'inherit'] });
execFileSync('node', ['scripts/agent-hooks/guarded-publish.mjs'], { input: '{}', stdio: ['pipe', 'ignore', 'inherit'] });
execFileSync('node', ['scripts/agent-hooks/staged-diff-guard.mjs'], { stdio: 'inherit' });

const tempDir = mkdtempSync(join(tmpdir(), 'sociopro-commit-msg-'));
const messageFile = join(tempDir, 'COMMIT_EDITMSG');

try {
    writeFileSync(messageFile, 'chore(agent-hooks): verify guardrail scripts\n');
    execFileSync('node', ['scripts/agent-hooks/commit-msg-guard.mjs', messageFile], { stdio: 'inherit' });
} finally {
    rmSync(tempDir, { force: true, recursive: true });
}

console.log('Sociopro hook smoke checks passed.');
