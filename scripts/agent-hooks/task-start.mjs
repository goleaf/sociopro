import { changedPaths, checksForPaths, docsForSubagents, git, hookContext, markdownFiles, readHookPayload, selectSubagents, stackSummary } from './hook-lib.mjs';

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
const suggested = selectSubagents(prompt, dirty);
const docsToLoad = docsForSubagents(suggested);
const requiredChecks = checksForPaths(dirty);

const dirtySummary = dirty.length === 0
    ? 'clean'
    : `${dirty.length} dirty path(s): ${dirty.slice(0, 8).join(', ')}${dirty.length > 8 ? ', ...' : ''}`;

const context = [
    'Sociopro task-start context:',
    `- Branch: ${branch || 'unknown'}; worktree: ${dirtySummary}. Inspect git status before edits and stage only the requested slice.`,
    `- Live stack from lock/config files: PHP ${stack.php}, Laravel ${stack.laravel}, Sanctum ${stack.sanctum}, PHPUnit ${stack.phpunit}, Pint ${stack.pint}, Larastan ${stack.larastan}, Rector ${stack.rector}, npm lock ${stack.npmLockfile}.`,
    `- Markdown corpus: ${docsCount} tracked docs/rules files. Load these docs for this prompt: ${docsToLoad.slice(0, 14).join(', ')}${docsToLoad.length > 14 ? ', ...' : ''}.`,
    `- Suggested project subagents for this task: ${suggested.join(', ')}. Briefs live in .agents/subagents/ and Claude wrappers in .claude/agents/.`,
    `- Likely verification from current dirty paths: ${requiredChecks.join(' && ')}.`,
    '- Always prefer current checkout evidence over stale audit docs. Some older docs still mention Laravel 9, Mix, or missing tools.',
    '- End-of-task discipline: run relevant checks, update docs when behavior/tooling/deployment/security changes, review diff for secrets/raw SQL/Blade queries, then commit and push only the intended slice.',
].join('\n');

console.log(hookContext(context, hookEventName));
