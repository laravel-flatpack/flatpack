import { afterEach, describe, expect, it } from 'vitest';
import {
    cn,
    getCurrentPath,
    getRoutePathname,
    isSamePath,
    normalizePathname,
    toUrl,
} from '@/lib/utils';

describe('cn', () => {
    it('merges class lists and resolves tailwind conflicts', () => {
        expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    });

    it('ignores falsy inputs', () => {
        expect(cn('a', false, undefined, 'b')).toBe('a b');
    });
});

describe('toUrl', () => {
    it('returns string hrefs unchanged', () => {
        expect(toUrl('/posts')).toBe('/posts');
    });

    it('reads url from object href', () => {
        expect(toUrl({ url: '/inertia', method: 'get' })).toBe('/inertia');
    });
});

describe('getCurrentPath', () => {
    const originalHref = window.location.href;

    afterEach(() => {
        window.history.replaceState(null, '', originalHref);
    });

    it('uses window.location.pathname when window is available', () => {
        window.history.pushState(null, '', '/dashboard?x=1');
        expect(getCurrentPath('/ignored')).toBe('/dashboard');
    });

    it('strips the query string when window is unavailable', () => {
        const prev = globalThis.window;
        try {
            // @ts-expect-error — exercise SSR-style branch
            globalThis.window = undefined;
            expect(getCurrentPath('/path?query=1')).toBe('/path');
        } finally {
            globalThis.window = prev;
        }
    });
});

describe('normalizePathname', () => {
    it('parses relative paths against the document origin', () => {
        expect(normalizePathname('foo/bar')).toBe('/foo/bar');
    });

    it('removes trailing slashes except for root', () => {
        expect(normalizePathname('/a/b/')).toBe('/a/b');
        expect(normalizePathname('/')).toBe('/');
        expect(normalizePathname('')).toBe('/');
    });

    it('strips query and hash from absolute URLs', () => {
        expect(normalizePathname('https://example.com/page?x=1#h')).toBe(
            '/page',
        );
    });
});

describe('getRoutePathname', () => {
    it('delegates to normalizePathname', () => {
        expect(getRoutePathname('/route/')).toBe('/route');
    });
});

describe('isSamePath', () => {
    it('treats equivalent paths as equal', () => {
        expect(isSamePath('/a', '/a/')).toBe(true);
        expect(isSamePath('/a?x=1', '/a#frag')).toBe(true);
    });

    it('distinguishes different pathnames', () => {
        expect(isSamePath('/a', '/b')).toBe(false);
    });
});
