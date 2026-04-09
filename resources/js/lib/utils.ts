import type { InertiaLinkProps } from '@inertiajs/react';
import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function getCurrentPath(url: string): string {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }

    return url.split('?')[0];
}

export function normalizePathname(value: string): string {
    const fallbackOrigin =
        typeof window !== 'undefined'
            ? window.location.origin
            : 'http://localhost';
    const pathname = new URL(value, fallbackOrigin).pathname;

    return pathname.replace(/\/+$/, '') || '/';
}

export function getRoutePathname(route: string): string {
    return normalizePathname(route);
}

export function isSamePath(a: string, b: string): boolean {
    return normalizePathname(a) === normalizePathname(b);
}
