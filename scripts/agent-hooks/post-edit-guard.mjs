import { changedPaths, git, hookContext, isApplicationPath, isDocumentationPath, limitLines, readHookPayload } from './hook-lib.mjs';

await readHookPayload();

const diff = git(['diff', '--unified=0', '--', 'app', 'routes', 'resources', 'config', 'database', 'tests', 'composer.json', 'package.json', 'vite.config.js', 'postcss.config.cjs', 'phpunit.xml', 'pint.json', 'phpstan.neon', 'rector.php', 'AGENTS.md', 'README.md', 'docs', '.agents']);
const findings = [];
let currentFile = null;

for (const line of diff.split('\n')) {
    if (line.startsWith('+++ b/')) {
        currentFile = line.slice(6);
        continue;
    }

    if (!currentFile || !line.startsWith('+') || line.startsWith('+++')) {
        continue;
    }

    const added = line.slice(1);

    if (/\b(dd|dump|ray|var_dump|print_r)\s*\(|console\.log\s*\(|\bdebugger\b/.test(added)) {
        findings.push(`${currentFile}: debug output added`);
    }

    if (!currentFile.startsWith('config/') && /\benv\s*\(/.test(added)) {
        findings.push(`${currentFile}: env() outside config added`);
    }

    if (/^(app|routes|resources)\//.test(currentFile) && /\bDB::(select|statement|raw|unprepared)\s*\(/.test(added)) {
        findings.push(`${currentFile}: forbidden DB raw/select/statement/unprepared added`);
    }

    if (currentFile.endsWith('.blade.php') && /(DB::|\\App\\Models|::where\s*\(|::query\s*\(|->count\s*\(|->sum\s*\(|@php)/.test(added)) {
        findings.push(`${currentFile}: Blade query/business-logic hotspot added`);
    }

    if (/\$request->all\s*\(\)/.test(added)) {
        findings.push(`${currentFile}: request()->all style input access added`);
    }

    if (/\$guarded\s*=\s*\[\s*\]/.test(added)) {
        findings.push(`${currentFile}: guarded = [] added`);
    }

    if (/::all\s*\(\)/.test(added)) {
        findings.push(`${currentFile}: unbounded ::all() added`);
    }
}

const changed = changedPaths();
const applicationChanged = changed.some((path) => isApplicationPath(path));
const documentationChanged = changed.some((path) => isDocumentationPath(path) && path.endsWith('.md'));

if (applicationChanged && !documentationChanged) {
    findings.push('Documentation drift check: app/config/route/database/test/tooling files changed, but no tracked Markdown doc changed. If the change affects behavior, operations, security, commands, or agent rules, update docs in the same slice.');
}

if (findings.length === 0) {
    process.exit(0);
}

const context = [
    'Sociopro post-edit guard found follow-up checks:',
    ...limitLines([...new Set(findings)].map((finding) => `- ${finding}`), 14),
    'Before finalizing, either fix the issue, prove it is legacy pre-existing context in the final report, or update the appropriate docs/tests.',
].join('\n');

console.log(hookContext(context, 'PostToolUse'));
