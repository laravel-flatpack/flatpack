/**
 * Fail when public/build differs from HEAD (modified, deleted, or untracked).
 * Run after `npm run build` (see package.json `assets:check`).
 */
import { execSync } from 'node:child_process';

function hasDiffFromHead() {
    try {
        execSync('git diff --quiet HEAD -- public/build');
    } catch {
        return true;
    }

    return false;
}

const untracked = execSync('git ls-files --others --exclude-standard -- public/build', {
    encoding: 'utf8',
    maxBuffer: 10 * 1024 * 1024,
}).trim();

if (hasDiffFromHead() || untracked !== '') {
    console.error(
        '[flatpack] public/build is out of date. Run npm run build and commit the result.',
    );

    if (untracked !== '') {
        const lines = untracked.split('\n');
        const preview = lines.slice(0, 10).join('\n');
        console.error(
            `\nUntracked (${lines.length}):`,
            preview,
            lines.length > 10 ? '...' : '',
        );
    }

    if (hasDiffFromHead()) {
        const names = execSync('git diff --name-only HEAD -- public/build', {
            encoding: 'utf8',
            maxBuffer: 10 * 1024 * 1024,
        }).trim();

        if (names !== '') {
            const lines = names.split('\n');
            const preview = lines.slice(0, 10).join('\n');
            console.error(
                `\nChanged vs HEAD (${lines.length}):`,
                preview,
                lines.length > 10 ? '...' : '',
            );
        }
    }

    process.exit(1);
}
