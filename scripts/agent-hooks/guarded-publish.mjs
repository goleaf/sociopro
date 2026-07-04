import { changedPaths, git, hookContext, isApplicationPath, isDocumentationPath, limitLines, readHookPayload } from './hook-lib.mjs';

await readHookPayload();

const changed = changedPaths();

if (changed.length === 0) {
    process.exit(0);
}

const suspicious = changed.filter((path) => (
    /^\.env($|\.)/.test(path)
    || /^storage\//.test(path)
    || /^vendor\//.test(path)
    || /^node_modules\//.test(path)
    || /^public\/build\//.test(path)
    || /^bootstrap\/cache\//.test(path)
    || /^database\/.*\.sqlite/.test(path)
    || /(\.key|\.pem|\.p12|\.sql|\.dump|\.bak|\.backup)$/.test(path)
));

const appChanged = changed.some((path) => isApplicationPath(path));
const docsChanged = changed.some((path) => isDocumentationPath(path) && path.endsWith('.md'));

const requiredChecks = ['git diff --check'];

if (appChanged) {
    requiredChecks.push('php artisan test');
}

if (changed.some((path) => /\.(php|blade\.php)$/.test(path) || /^(app|routes|config|database|tests)\//.test(path))) {
    requiredChecks.unshift('vendor/bin/pint --test');
}

if (changed.some((path) => /^resources\/(js|scss)\//.test(path) || ['package.json', 'package-lock.json', 'vite.config.js', 'postcss.config.cjs'].includes(path))) {
    requiredChecks.push('npm run build');
}

const reminders = [
    'Sociopro guarded-publish reminder:',
    `- Dirty paths remain: ${changed.length}.`,
    ...limitLines(changed.map((path) => `  - ${path}`), 18),
];

if (suspicious.length > 0) {
    reminders.push(`- Blocker candidate: suspicious generated/secret-like paths present: ${suspicious.join(', ')}`);
}

if (appChanged && !docsChanged) {
    reminders.push('- Documentation check: no Markdown doc changed with application/tooling changes. Update docs or record why no docs were required.');
}

reminders.push(`- Required before commit/push: ${[...new Set(requiredChecks)].join(' && ')}.`);
reminders.push('- Commit policy: stage only intended files, scan staged diff for secrets/raw SQL/Blade queries, use Conventional Commit, verify intended commit is HEAD, then push.');
reminders.push('- This hook is advisory by default because the repo may contain pre-existing user work. Set SOCIOPRO_HOOK_ENFORCE_PUBLICATION=1 to make dirty end states block completion.');

const context = reminders.join('\n');

console.log(hookContext(context, 'Stop'));

if (process.env.SOCIOPRO_HOOK_ENFORCE_PUBLICATION === '1') {
    process.exit(2);
}
