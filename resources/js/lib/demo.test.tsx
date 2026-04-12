import type { ComponentType } from 'react';
import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import {
    buildDemoFieldRenderProps,
    demoCatalogToByType,
    lazyFieldMapFromCatalog,
    normalizeDemoComponentType,
    resolveDemoComponentSelection,
} from '@/lib/demo';
import type { DemoComponentCatalogEntry } from '@/types/demo';

vi.mock('@/lib/form', () => ({
    loadField: vi.fn(() =>
        React.lazy(
            () =>
                Promise.resolve({
                    default: function MockField() {
                        return <span data-testid="lazy-mock">ok</span>;
                    },
                }) as Promise<{
                    default: ComponentType<Record<string, unknown>>;
                }>,
        ),
    ),
}));

function textEntry(
    overrides: Partial<DemoComponentCatalogEntry> = {},
): DemoComponentCatalogEntry {
    return {
        id: 'e-text',
        title: 'Text',
        description: 'd',
        value: null,
        showValue: true,
        props: { type: 'text', label: 'L', placeholder: 'p' },
        ...overrides,
    };
}

describe('demoCatalogToByType', () => {
    it('indexes entries by props.type (last wins)', () => {
        const a = textEntry({ id: '1', title: 'First' });
        const b = textEntry({ id: '2', title: 'Second' });
        const map = demoCatalogToByType([a, b]);
        expect(map.text.id).toBe('2');
    });
});

describe('normalizeDemoComponentType', () => {
    const byType = demoCatalogToByType([textEntry()]);

    it('returns null for empty input', () => {
        expect(normalizeDemoComponentType(null, byType)).toBeNull();
        expect(normalizeDemoComponentType('', byType)).toBeNull();
    });

    it('matches case-insensitively', () => {
        expect(normalizeDemoComponentType('TEXT', byType)).toBe('text');
    });

    it('returns null for unknown type', () => {
        expect(normalizeDemoComponentType('nope', byType)).toBeNull();
    });
});

describe('resolveDemoComponentSelection', () => {
    const catalog = [
        textEntry(),
        textEntry({ props: { type: 'textarea', label: 'T' } }),
    ];
    const byType = demoCatalogToByType(catalog);

    it('returns normalized type and no error when selector valid', () => {
        const r = resolveDemoComponentSelection({ type: 'text' }, byType);
        expect(r.normalized).toBe('text');
        expect(r.requestedUnknown).toBe(false);
    });

    it('flags requestedUnknown for invalid type', () => {
        const r = resolveDemoComponentSelection({ type: 'unknown' }, byType);
        expect(r.normalized).toBeNull();
        expect(r.requestedUnknown).toBe(true);
        expect(r.selectorRaw).toBe('unknown');
    });
});

describe('lazyFieldMapFromCatalog', () => {
    it('creates one lazy entry per distinct type', () => {
        const catalog = [
            textEntry({ id: 'a' }),
            textEntry({ id: 'b' }),
            textEntry({
                id: 'c',
                props: { type: 'textarea', label: 'x' },
            }),
        ];
        const map = lazyFieldMapFromCatalog(catalog);
        expect(Object.keys(map).sort()).toEqual(['text', 'textarea']);
    });
});

describe('buildDemoFieldRenderProps', () => {
    it('merges query overrides then maps to render props', () => {
        const onValueChange = vi.fn();
        const entry = textEntry({
            props: { type: 'text', label: 'A', placeholder: 'B' },
        });
        const out = buildDemoFieldRenderProps(entry, {
            queryOverrides: { label: 'From query' },
            onValueChange,
        });
        expect(out.label).toBe('From query');
        expect(out.placeholder).toBe('B');
        expect(out.onValueChange).toBe(onValueChange);
        expect(out.id).toBe('e-text');
    });
});
