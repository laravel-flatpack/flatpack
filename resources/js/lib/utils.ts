import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

import { route } from '@/lib/route';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function getCurrentPath(url: string): string {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }

    return url.split('?')[0];
}

function normalizePathname(value: string): string {
    const fallbackOrigin =
        typeof window !== 'undefined'
            ? window.location.origin
            : 'http://localhost';
    const pathname = new URL(value, fallbackOrigin).pathname;

    return pathname.replace(/\/+$/, '') || '/';
}

/**
 * Menu `url` values may be absolute URLs, root-relative paths, or Ziggy route names (config overrides).
 */
function resolveNavListPathname(listRoute: string): string {
    const trimmed = listRoute.trim();
    if (trimmed === '') {
        return '/';
    }
    if (trimmed.startsWith('/') || /^https?:\/\//i.test(trimmed)) {
        return normalizePathname(trimmed);
    }

    try {
        const resolved = route(trimmed);

        return normalizePathname(typeof resolved === 'string' ? resolved : String(resolved));
    } catch {
        return normalizePathname(trimmed);
    }
}

/**
 * Sidebar items link to the entity list. Mark active on list, create, and edit routes.
 */
export function isEntityListNavActive(
    currentPath: string,
    listRoute: string,
): boolean {
    const path = normalizePathname(currentPath);
    const list = resolveNavListPathname(listRoute);

    if (path === list) {
        return true;
    }

    if (!path.startsWith(`${list}/`)) {
        return false;
    }

    const rest = path.slice(list.length + 1);

    if (rest === 'create') {
        return true;
    }

    return /^[^/]+\/edit$/.test(rest);
}
