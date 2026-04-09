/**
 * After Wayfinder generates TypeScript:
 * 1. Drop Testbench-only `routes/workbench` (not part of Flatpack).
 * 2. Remove `* @see` docblock lines that point at local filesystem paths.
 *
 * Note: Wayfinder emits a second @see line with a .php path for "jump to definition". Under
 * Orchestra Testbench, `base_path()` often does not strip the package path, so the
 * generated comment can contain the developer's absolute machine path — unsafe to commit.
 *
 * We keep lines like `* @see \App\Http\Controllers\Foo::bar` (PHP FQCN, starts with `\`).
 */
import { existsSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const packageRoot = dirname(dirname(fileURLToPath(import.meta.url)));

const workbenchRoutes = resolve(packageRoot, 'resources/js/routes/workbench');
let removedWorkbench = false;
if (existsSync(workbenchRoutes)) {
    rmSync(workbenchRoutes, { recursive: true, force: true });
    removedWorkbench = true;
}

/** Strip `* @see` lines that are not PHP symbol references (those start with `\`). */
function stripFilesystemSeeLines(content) {
    return content.replace(/^\s*\*\s*@see\s+(?!\\)[^\r\n]*\r?\n/gm, '');
}

function walkTsFiles(dir, out = []) {
    if (!existsSync(dir)) {
        return out;
    }
    for (const name of readdirSync(dir)) {
        const full = join(dir, name);
        if (statSync(full).isDirectory()) {
            walkTsFiles(full, out);
        } else if (name.endsWith('.ts')) {
            out.push(full);
        }
    }
    return out;
}

let sanitizedFiles = 0;

for (const rel of ['resources/js/routes', 'resources/js/actions']) {
    const abs = resolve(packageRoot, rel);
    for (const file of walkTsFiles(abs)) {
        const before = readFileSync(file, 'utf8');
        const after = stripFilesystemSeeLines(before);
        if (after !== before) {
            writeFileSync(file, after, 'utf8');
            sanitizedFiles += 1;
        }
    }
}

const workbenchNote = removedWorkbench
    ? 'workbench routes removed'
    : 'no workbench routes dir';

console.log('\n');
console.log(` \x1b[32m[Flatpack] Wayfinder postprocess done: \x1b[0m`);
console.log(`   ✅ ${workbenchNote}.`);
console.log(`   ✅ ${sanitizedFiles} file(s) stripped of filesystem @see lines.`);
console.log('\n');