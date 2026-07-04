import { docsRequiredForPaths, hookContext, isDocumentationPath, isRuntimeCodePath, limitLines, protectedPaths, stagedDiff, stagedPaths, staleDocWarningsForText } from './hook-lib.mjs';

const staged = stagedPaths();

if (staged.length === 0) {
    process.exit(0);
}

const findings = [];
const protectedStaged = protectedPaths(staged);

if (protectedStaged.length > 0) {
    findings.push(`Protected/generated/secret-like files are staged: ${protectedStaged.join(', ')}`);
}

const docsChanged = staged.some((path) => isDocumentationPath(path) && path.endsWith('.md'));

if (docsRequiredForPaths(staged) && !docsChanged) {
    findings.push('Documentation update is required for this staged slice. Stage a relevant Markdown update with the code/tooling change.');
}

let currentFile = null;
const diff = stagedDiff();

for (const line of diff.split('\n')) {
    if (line.startsWith('+++ b/')) {
        currentFile = line.slice(6);
        continue;
    }

    if (!currentFile || !line.startsWith('+') || line.startsWith('+++')) {
        continue;
    }

    const added = line.slice(1);
    const runtimeCode = isRuntimeCodePath(currentFile);

    if (runtimeCode && /\b(dd|dump|ray|var_dump|print_r)\s*\(|console\.log\s*\(|\bdebugger\b/.test(added)) {
        findings.push(`${currentFile}: debug output staged`);
    }

    if (runtimeCode && !currentFile.startsWith('config/') && /\benv\s*\(/.test(added)) {
        findings.push(`${currentFile}: env() staged outside config`);
    }

    if (runtimeCode && /^(app|routes|resources)\//.test(currentFile) && /\bDB::(select|statement|raw|unprepared)\s*\(/.test(added)) {
        findings.push(`${currentFile}: forbidden raw DB API staged`);
    }

    if (currentFile.endsWith('.blade.php') && /(DB::|\\App\\Models|::where\s*\(|::query\s*\(|->count\s*\(|->sum\s*\(|@php)/.test(added)) {
        findings.push(`${currentFile}: Blade query/business-logic hotspot staged`);
    }

    if (runtimeCode && /\$request->all\s*\(\)/.test(added)) {
        findings.push(`${currentFile}: $request->all() staged`);
    }

    if (runtimeCode && /\$guarded\s*=\s*\[\s*\]/.test(added)) {
        findings.push(`${currentFile}: guarded = [] staged`);
    }

    if (runtimeCode && /::all\s*\(\)/.test(added)) {
        findings.push(`${currentFile}: unbounded ::all() staged`);
    }

    if (/-----BEGIN (RSA |OPENSSH |EC |)PRIVATE KEY-----/.test(added)) {
        findings.push(`${currentFile}: private key material staged`);
    }

    if (/(api[_-]?key|client[_-]?secret|secret|token|password)\s*[:=]\s*['"][^'"]{12,}['"]/i.test(added)
        && !/(placeholder|example|dummy|fake|local|testing|redacted|changeme)/i.test(added)) {
        findings.push(`${currentFile}: secret-like value staged`);
    }

    if (currentFile.endsWith('.md')) {
        for (const warning of staleDocWarningsForText(added)) {
            findings.push(`${currentFile}: historical/stale baseline wording staged (${warning})`);
        }
    }
}

if (findings.length === 0) {
    process.exit(0);
}

const message = [
    'Sociopro staged-diff guard blocked this commit:',
    ...limitLines([...new Set(findings)].map((finding) => `- ${finding}`), 18),
    'Fix the staged diff, unstage unrelated files, or document an intentional exception before committing.',
].join('\n');

console.error(message);
console.log(hookContext(message, 'pre-commit'));
process.exit(1);
