import type { LucideIcon } from 'lucide-react';
import {
    DynamicIcon,
    dynamicIconImports,
    type IconName,
} from 'lucide-react/dynamic';

/**
 * `lucide-react` dynamic imports are keyed by **kebab-case** ids (same strings as lucide.dev).
 * Use this to normalize config values (snake_case, PascalCase, `*Icon` suffix).
 */
export function normalizeToLucideKebabCase(name: string): string {
    const trimmed = name.trim();
    if (!trimmed) {
        return trimmed;
    }

    let base = trimmed;
    if (/Icon$/i.test(base) && base.length > 4) {
        base = base.slice(0, -4);
    }

    if (base.includes('_')) {
        return base.toLowerCase().replaceAll('_', '-');
    }

    if (!/[A-Z]/.test(base)) {
        return base.toLowerCase();
    }

    return base
        .replace(/([a-z])([A-Z])/g, '$1-$2')
        .replace(/([A-Z])([A-Z][a-z])/g, '$1-$2')
        .toLowerCase();
}

function resolveLucideIconKey(name: string): IconName | null {
    const trimmed = name.trim();
    if (!trimmed) {
        return null;
    }

    if (Object.hasOwn(dynamicIconImports, trimmed)) {
        return trimmed as IconName;
    }

    const kebab = normalizeToLucideKebabCase(trimmed);
    if (Object.hasOwn(dynamicIconImports, kebab)) {
        return kebab as IconName;
    }

    return null;
}

/**
 * Whether `name` resolves to a Lucide icon. Accepts kebab-case keys or common variants
 * (e.g. `book_open`, `BookOpen`, `BookOpenIcon` → `book-open`).
 */
export function hasLucideIcon(name: string): boolean {
    return resolveLucideIconKey(name) !== null;
}

/**
 * Loads the icon component for a valid name. Unknown names resolve to `null`.
 * Prefer {@link LucideIconByName} in React; this is for non-UI or imperative use.
 */
export async function loadLucideIcon(name: string): Promise<LucideIcon | null> {
    const key = resolveLucideIconKey(name);
    if (key === null) {
        return null;
    }
    const mod = await dynamicIconImports[key]();
    return mod.default;
}

/**
 * Renders a Lucide icon by name. Lucide’s registry keys are kebab-case; input is normalized
 * and resolved to `null` if unknown (no console error; icons load on demand per name).
 */
export function LucideIconByName({
    name,
    className,
}: {
    name: string;
    className?: string;
}) {
    const key = resolveLucideIconKey(name);
    if (key === null) {
        return null;
    }

    return <DynamicIcon name={key} className={className} />;
}
