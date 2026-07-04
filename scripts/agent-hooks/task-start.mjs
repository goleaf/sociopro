import { changedPaths, git, hookContext, markdownFiles, readHookPayload, stackSummary } from './hook-lib.mjs';

const payload = await readHookPayload();
const hookEventName = payload.hook_event_name
    || payload.hookEventName
    || payload.event
    || 'UserPromptSubmit';
const prompt = [
    payload.prompt,
    payload.user_prompt,
    payload.userPrompt,
    payload.message,
    payload.raw,
].filter(Boolean).join('\n');

const stack = stackSummary();
const dirty = changedPaths();
const docsCount = markdownFiles().length;
const branch = git(['branch', '--show-current'], 'unknown');

const taskSignals = [
    [/blade|view|frontend|scss|css|javascript|vite|ui|design/i, 'frontend-blade-accessibility-guardian'],
    [/api|sanctum|json|endpoint|token|resource/i, 'api-contract-guardian'],
    [/migration|schema|database|index|query|eloquent|performance|n\+1/i, 'database-query-migration-guardian'],
    [/security|auth|policy|secret|webhook|payment|upload|csrf|idor/i, 'security-payment-guardian'],
    [/test|phpunit|pint|phpstan|rector|quality|ci|build|lint/i, 'quality-release-guardian'],
    [/doc|readme|audit|standard|agent|hook|subagent|prompt/i, 'documentation-context-curator'],
];

const suggested = taskSignals
    .filter(([pattern]) => pattern.test(prompt))
    .map(([, name]) => name);

if (suggested.length === 0) {
    suggested.push('repo-steward');
}

const dirtySummary = dirty.length === 0
    ? 'clean'
    : `${dirty.length} dirty path(s): ${dirty.slice(0, 8).join(', ')}${dirty.length > 8 ? ', ...' : ''}`;

const context = [
    'Sociopro task-start context:',
    `- Branch: ${branch || 'unknown'}; worktree: ${dirtySummary}. Inspect git status before edits and stage only the requested slice.`,
    `- Live stack from lock/config files: PHP ${stack.php}, Laravel ${stack.laravel}, Sanctum ${stack.sanctum}, PHPUnit ${stack.phpunit}, Pint ${stack.pint}, Larastan ${stack.larastan}, Rector ${stack.rector}, npm lock ${stack.npmLockfile}.`,
    `- Markdown corpus: ${docsCount} tracked docs/rules files. Source-of-truth start set: AGENTS.md, docs/project-standards-bible.md, docs/coding-standards.md, docs/code-quality-standards.md, docs/local-quality-commands.md, docs/refactor-checklist.md, docs/refactor-roadmap-unreal.md.`,
    `- Suggested project subagents for this task: ${suggested.join(', ')}. See .agents/subagents/ for role briefs.`,
    '- Always prefer current checkout evidence over stale audit docs. Some older docs still mention Laravel 9, Mix, or missing tools.',
    '- End-of-task discipline: run relevant checks, update docs when behavior/tooling/deployment/security changes, review diff for secrets/raw SQL/Blade queries, then commit and push only the intended slice.',
].join('\n');

console.log(hookContext(context, hookEventName));
