import { changedPaths, checksForPaths, docsRequiredForPaths, hookContext, isDocumentationPath, limitLines, protectedPaths, readHookPayload, selectSubagents } from './hook-lib.mjs';

await readHookPayload();

const changed = changedPaths();

if (changed.length === 0) {
    process.exit(0);
}

const suspicious = protectedPaths(changed);
const docsChanged = changed.some((path) => isDocumentationPath(path) && path.endsWith('.md'));
const docsRequired = docsRequiredForPaths(changed);
const requiredChecks = checksForPaths(changed);
const suggested = selectSubagents('', changed);

const reminders = [
    'Sociopro guarded-publish reminder:',
    `- Dirty paths remain: ${changed.length}.`,
    ...limitLines(changed.map((path) => `  - ${path}`), 18),
];

if (suspicious.length > 0) {
    reminders.push(`- Blocker candidate: suspicious generated/secret-like paths present: ${suspicious.join(', ')}`);
}

if (docsRequired && !docsChanged) {
    reminders.push('- Documentation check: changed paths require a Markdown update before commit.');
}

reminders.push(`- Review routing: ${suggested.join(', ')}.`);
reminders.push(`- Required before commit/push: ${[...new Set(requiredChecks)].join(' && ')}.`);
reminders.push('- Commit policy: stage only intended files, scan staged diff for secrets/raw SQL/Blade queries, use Conventional Commit, verify intended commit is HEAD, then push.');
reminders.push('- This hook is advisory by default because the repo may contain pre-existing user work. Set SOCIOPRO_HOOK_ENFORCE_PUBLICATION=1 to make dirty end states block completion.');

const context = reminders.join('\n');

console.log(hookContext(context, 'Stop'));

if (process.env.SOCIOPRO_HOOK_ENFORCE_PUBLICATION === '1') {
    process.exit(2);
}
