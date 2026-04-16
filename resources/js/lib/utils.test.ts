import { afterEach, describe, expect, it } from 'vitest';
import { cn, getCurrentPath, isEntityListNavActive } from '@/lib/utils';

describe('cn', () => {
    it('merges class lists and resolves tailwind conflicts', () => {
        expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    });

    it('ignores falsy inputs', () => {
        expect(cn('a', false, undefined, 'b')).toBe('a b');
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

describe('isEntityListNavActive', () => {
    const list = '/flatpack/posts';

    it('is active on the list path', () => {
        expect(isEntityListNavActive(`${list}/`, list)).toBe(true);
    });

    it('is active on create', () => {
        expect(isEntityListNavActive(`${list}/create`, list)).toBe(true);
    });

    it('is active on edit', () => {
        expect(isEntityListNavActive(`${list}/42/edit`, list)).toBe(true);
        expect(isEntityListNavActive(`${list}/uuid-here/edit`, list)).toBe(
            true,
        );
    });

    it('is not active on another entity list', () => {
        expect(isEntityListNavActive('/flatpack/other', list)).toBe(false);
    });

    it('is not active on unrelated subpaths', () => {
        expect(isEntityListNavActive(`${list}/relation-options`, list)).toBe(
            false,
        );
    });
});
