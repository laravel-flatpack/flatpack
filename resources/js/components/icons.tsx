import type { LucideIcon } from 'lucide-react';
import { flatpackMenuIcons } from './lucide-menu-icon-registry';

/**
 * Normalize config values (snake_case, PascalCase, `*Icon` suffix) toward Lucide kebab-case ids.
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

function resolveMenuIconKey(
    name: string,
): keyof typeof flatpackMenuIcons | null {
    const trimmed = name.trim();
    if (!trimmed) {
        return null;
    }

    if (Object.hasOwn(flatpackMenuIcons, trimmed)) {
        return trimmed as keyof typeof flatpackMenuIcons;
    }

    const kebab = normalizeToLucideKebabCase(trimmed);
    if (Object.hasOwn(flatpackMenuIcons, kebab)) {
        return kebab as keyof typeof flatpackMenuIcons;
    }

    return null;
}

/**
 * Whether `name` resolves to a bundled menu icon.
 */
export function hasLucideIcon(name: string): boolean {
    return resolveMenuIconKey(name) !== null;
}

/**
 * Resolves the icon component for a valid menu name. Unknown names resolve to `null`.
 */
export async function loadLucideIcon(name: string): Promise<LucideIcon | null> {
    const key = resolveMenuIconKey(name);
    if (key === null) {
        return null;
    }
    return flatpackMenuIcons[key];
}

/**
 * Renders a menu icon by name. Unknown names render nothing (same as before when using dynamic Lucide).
 */
export function LucideIconByName({
    name,
    className,
}: {
    name: string;
    className?: string;
}) {
    const key = resolveMenuIconKey(name);
    if (key === null) {
        return null;
    }

    const Icon = flatpackMenuIcons[key];
    return <Icon className={className} />;
}
