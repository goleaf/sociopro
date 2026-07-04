import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

export const scriptDir = dirname(fileURLToPath(import.meta.url));
export const repoRoot = findRepoRoot();

function findRepoRoot() {
    try {
        return execFileSync('git', ['rev-parse', '--show-toplevel'], {
            cwd: process.cwd(),
            encoding: 'utf8',
            stdio: ['ignore', 'pipe', 'ignore'],
        }).trim();
    } catch {
        return resolve(scriptDir, '../..');
    }
}

export function git(args, fallback = '') {
    try {
        return execFileSync('git', args, {
            cwd: repoRoot,
            encoding: 'utf8',
            stdio: ['ignore', 'pipe', 'pipe'],
        }).trimEnd();
    } catch (error) {
        return fallback || String(error.stderr || error.message || '').trim();
    }
}

export function readJson(path, fallback = null) {
    try {
        return JSON.parse(readFileSync(join(repoRoot, path), 'utf8'));
    } catch {
        return fallback;
    }
}

export function readText(path, fallback = '') {
    try {
        return readFileSync(join(repoRoot, path), 'utf8');
    } catch {
        return fallback;
    }
}

export async function readHookPayload() {
    const chunks = [];

    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }

    const input = Buffer.concat(chunks).toString('utf8').trim();

    if (!input) {
        return {};
    }

    try {
        return JSON.parse(input);
    } catch {
        return { raw: input };
    }
}

export function statusEntries() {
    const output = git(['status', '--porcelain=v1']);

    if (!output) {
        return [];
    }

    return output.split('\n').map((line) => {
        const status = line.slice(0, 2);
        let path = line.slice(3);

        if (path.includes(' -> ')) {
            path = path.split(' -> ').at(-1);
        }

        return { status, path };
    });
}

export function changedPaths() {
    return statusEntries().map((entry) => entry.path);
}

export function stagedPaths() {
    const output = git(['diff', '--cached', '--name-only', '--diff-filter=ACMR']);

    return output ? output.split('\n').filter(Boolean) : [];
}

export function stagedDiff(paths = []) {
    const args = ['diff', '--cached', '--unified=0'];

    if (paths.length > 0) {
        args.push('--', ...paths);
    }

    return git(args);
}

export function markdownFiles() {
    const output = git(['ls-files', '*.md']);

    return output ? output.split('\n').filter(Boolean) : [];
}

export function fileExists(path) {
    return existsSync(join(repoRoot, path));
}

export function isDocumentationPath(path) {
    return path === 'AGENTS.md'
        || path === 'README.md'
        || path === 'DESIGN.md'
        || path === 'PRODUCT.md'
        || path.startsWith('docs/')
        || path.startsWith('.agents/');
}

export function isApplicationPath(path) {
    return /^(app|routes|resources|config|database|tests)\//.test(path)
        || ['composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js', 'postcss.config.cjs', 'phpunit.xml', 'pint.json', 'phpstan.neon', 'rector.php'].includes(path);
}

export function isRuntimeCodePath(path) {
    return /^(app|routes|config|database|tests)\//.test(path)
        || /^resources\/views\/.*\.blade\.php$/.test(path)
        || /^resources\/js\/.*\.js$/.test(path)
        || path.endsWith('.php');
}

export function stackSummary() {
    const composer = readJson('composer.json', {});
    const composerLock = readJson('composer.lock', {});
    const packageJson = readJson('package.json', {});
    const packageLock = readJson('package-lock.json', {});

    const packageVersion = (name) => {
        const packages = [
            ...(composerLock.packages || []),
            ...(composerLock['packages-dev'] || []),
        ];

        return packages.find((pkg) => pkg.name === name)?.version || 'not locked';
    };

    return {
        php: composer.require?.php || 'unknown',
        laravel: packageVersion('laravel/framework'),
        sanctum: packageVersion('laravel/sanctum'),
        phpunit: packageVersion('phpunit/phpunit'),
        pint: packageVersion('laravel/pint'),
        larastan: packageVersion('larastan/larastan'),
        rector: packageVersion('rector/rector'),
        npmLockfile: packageLock.lockfileVersion ? `v${packageLock.lockfileVersion}` : 'unknown',
        frontendScripts: Object.keys(packageJson.scripts || {}).join(', ') || 'none',
    };
}

export function agentRouting() {
    return readJson('.agents/agent-routing.json', {
        sourceOfTruthDocs: [
            'AGENTS.md',
            'docs/project-standards-bible.md',
            'docs/coding-standards.md',
            'docs/local-quality-commands.md',
        ],
        historicalDocWarnings: [],
        subagents: {
            'repo-steward': {
                brief: '.agents/subagents/repo-steward.md',
                signals: ['unknown'],
                docs: ['AGENTS.md', 'docs/project-standards-bible.md'],
                checks: ['git diff --check'],
            },
        },
        requiredChecksByPath: [],
        documentationRequiredForPath: [],
        protectedPathPatterns: [],
    });
}

export function unique(values) {
    return [...new Set(values.filter(Boolean))];
}

export function patternMatchesPath(pattern, path) {
    try {
        return new RegExp(pattern).test(path);
    } catch {
        return false;
    }
}

export function globMatchesPath(glob, path) {
    const escaped = glob
        .replace(/[.+^${}()|[\]\\]/g, '\\$&')
        .replace(/\*\*/g, '__DOUBLE_STAR__')
        .replace(/\*/g, '[^/]*');

    return new RegExp(`^${escaped.replace(/__DOUBLE_STAR__/g, '.*')}$`).test(path);
}

export function selectSubagents(prompt = '', paths = []) {
    const routing = agentRouting();
    const selected = [];
    const text = prompt.toLowerCase();

    for (const [name, config] of Object.entries(routing.subagents || {})) {
        const signalHit = (config.signals || []).some((signal) => text.includes(signal.toLowerCase()));
        const pathHit = (config.paths || []).some((pattern) => paths.some((path) => (
            pattern.includes('*') ? globMatchesPath(pattern, path) : path === pattern || path.startsWith(`${pattern.replace(/\/$/, '')}/`)
        )));

        if (signalHit || pathHit) {
            selected.push(name);
        }
    }

    return selected.length > 0 ? unique(selected) : ['repo-steward'];
}

export function docsForSubagents(subagents = []) {
    const routing = agentRouting();
    const docs = [...(routing.sourceOfTruthDocs || [])];

    for (const name of subagents) {
        docs.push(...(routing.subagents?.[name]?.docs || []));
    }

    return unique(docs);
}

export function checksForPaths(paths = []) {
    const routing = agentRouting();
    const checks = ['git diff --check'];

    for (const path of paths) {
        for (const rule of routing.requiredChecksByPath || []) {
            if (patternMatchesPath(rule.pattern, path)) {
                checks.push(...(rule.checks || []));
            }
        }
    }

    return unique(checks);
}

export function docsRequiredForPaths(paths = []) {
    const routing = agentRouting();

    return paths.some((path) => (routing.documentationRequiredForPath || []).some((pattern) => patternMatchesPath(pattern, path)));
}

export function protectedPaths(paths = []) {
    const routing = agentRouting();

    return paths.filter((path) => (routing.protectedPathPatterns || []).some((pattern) => patternMatchesPath(pattern, path)));
}

export function staleDocWarningsForText(text = '') {
    const routing = agentRouting();

    if (/\b(do not|don't|never|avoid|removed|historical|stale|old|current .* wins|live checkout)\b/i.test(text)) {
        return [];
    }

    return (routing.historicalDocWarnings || []).filter((pattern) => text.includes(pattern));
}

export function hookContext(message, hookEventName = 'PostToolUse') {
    return JSON.stringify({
        additionalContext: message,
        hookSpecificOutput: {
            hookEventName,
            additionalContext: message,
        },
    });
}

export function limitLines(lines, count = 12) {
    if (lines.length <= count) {
        return lines;
    }

    return [
        ...lines.slice(0, count),
        `... ${lines.length - count} more omitted`,
    ];
}
