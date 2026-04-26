import { describe, expect, it } from 'vitest';
import {
    applyPresetCascade,
    buildInitialPresetBlockedIds,
    buildPresetEdgesBySourceId,
    formatInputValue,
    formatInputValueOnBlur,
    formatPresetValue,
    parsePreset,
} from '@/lib/form-field-preset';
import { serializeFieldValue } from '@/lib/form-page-field-values';
import type { FormFieldEntry } from '@/lib/form-schema';
import type { FormFieldProps } from '@/types/form-fields';

const text = (id: string, extra?: Partial<FormFieldProps>): FormFieldEntry => ({
    id,
    field: {
        type: 'text',
        label: id,
        ...(extra as object),
    } as FormFieldProps,
});

describe('formatPresetValue', () => {
    it('slugifies and strips diacritics', () => {
        expect(formatPresetValue('  Héllo  World ', 'slug')).toBe(
            'hello-world',
        );
    });

    it('url prefixes with a slash from slug', () => {
        expect(formatPresetValue('Hello world', 'url')).toBe('/hello-world');
        expect(formatPresetValue('   ', 'url')).toBe('');
    });

    it('exact copies as string', () => {
        expect(formatPresetValue('  x ', 'exact')).toBe('  x ');
        expect(formatPresetValue(42, 'exact')).toBe('42');
    });

    it('camelCase joins words', () => {
        expect(formatPresetValue('hello world', 'camel')).toBe('helloWorld');
        expect(formatPresetValue('XML parser', 'camel')).toBe('xmlParser');
    });

    it('file replaces whitespace and strips path-ish characters', () => {
        expect(formatPresetValue('my file name', 'file')).toBe('my-file-name');
        expect(formatPresetValue('a/b:test?.txt', 'file')).toBe(
            'a-b-test-.txt',
        );
    });
});

describe('formatInputValue', () => {
    it('reuses preset formatters except exact', () => {
        expect(formatInputValue('Hello world', 'slug')).toBe('hello-world');
        expect(formatInputValue('Hello world', 'url')).toBe('/hello-world');
        expect(formatInputValue('hello world', 'camel')).toBe('helloWorld');
        expect(formatInputValue('my file name', 'file')).toBe('my-file-name');
    });

    it('keeps typed dashes and converts spaces to dashes for slug', () => {
        expect(formatInputValue('hello-', 'slug')).toBe('hello-');
        expect(formatInputValue('hello ', 'slug')).toBe('hello-');
        expect(formatInputValue('hello - world', 'slug')).toBe('hello-world');
    });

    it('trims trailing slug/url dash on blur', () => {
        expect(formatInputValueOnBlur('hello-', 'slug')).toBe('hello');
        expect(formatInputValueOnBlur('hello-', 'url')).toBe('/hello');
    });
});

describe('parsePreset', () => {
    it('returns preset for text fields with valid config', () => {
        expect(
            parsePreset({
                type: 'text',
                label: '',
                preset: { field: 'title', type: 'url' },
            }),
        ).toEqual({ field: 'title', type: 'url' });
    });

    it('returns null for invalid preset or field type', () => {
        expect(
            parsePreset({
                type: 'checkbox',
                label: '',
                preset: { field: 'title', type: 'url' },
            }),
        ).toBeNull();

        expect(
            parsePreset({
                type: 'text',
                label: '',
                preset: { field: '', type: 'url' },
            }),
        ).toBeNull();
    });
});

describe('buildPresetEdgesBySourceId', () => {
    it('builds edges and skips unknown source or self-reference', () => {
        const fields: FormFieldEntry[] = [
            text('title'),
            text('url', { preset: { field: 'title', type: 'url' } }),
            text('ignore', { preset: { field: 'missing', type: 'slug' } }),
            text('self', { preset: { field: 'self', type: 'slug' } }),
        ];
        const map = buildPresetEdgesBySourceId(fields);
        expect(map.get('title')).toEqual([
            { destId: 'url', preset: { field: 'title', type: 'url' } },
        ]);
        expect(map.get('missing')).toBeUndefined();
        expect(map.get('self')).toBeUndefined();
    });
});

describe('buildInitialPresetBlockedIds', () => {
    it('blocks non-empty destinations', () => {
        const fields: FormFieldEntry[] = [
            text('title'),
            text('url', { preset: { field: 'title', type: 'slug' } }),
        ];
        const blocked = buildInitialPresetBlockedIds(fields, {
            title: '',
            url: 'kept',
        });
        expect(blocked.has('url')).toBe(true);
    });
});

describe('applyPresetCascade', () => {
    const fieldsById = new Map<string, FormFieldProps>([
        ['title', { type: 'text', label: 'Title' }],
        [
            'url',
            {
                type: 'text',
                label: 'URL',
                preset: { field: 'title', type: 'url' },
            },
        ],
    ]);

    it('fills preset destination from source when allowed', () => {
        const edges = buildPresetEdgesBySourceId([
            text('title'),
            text('url', { preset: { field: 'title', type: 'url' } }),
        ]);

        const next = applyPresetCascade({
            changedSourceId: 'title',
            values: { title: 'Hello world', url: '' },
            fieldsById,
            edgesBySource: edges,
            userTouchedDest: new Set(),
            initialBlockedDest: new Set(),
        });

        expect(next.url).toBe('/hello-world');
    });

    it('does not overwrite when destination is user-touched', () => {
        const edges = buildPresetEdgesBySourceId([
            text('title'),
            text('url', { preset: { field: 'title', type: 'url' } }),
        ]);

        const next = applyPresetCascade({
            changedSourceId: 'title',
            values: { title: 'B', url: 'manual' },
            fieldsById,
            edgesBySource: edges,
            userTouchedDest: new Set(['url']),
            initialBlockedDest: new Set(),
        });

        expect(next.url).toBe('manual');
    });

    it('chains when intermediate destination is allowed', () => {
        const f: FormFieldEntry[] = [
            text('a'),
            text('b', { preset: { field: 'a', type: 'exact' } }),
            text('c', { preset: { field: 'b', type: 'exact' } }),
        ];
        const edges = buildPresetEdgesBySourceId(f);
        const byId = new Map(f.map(({ id, field }) => [id, field]));

        const next = applyPresetCascade({
            changedSourceId: 'a',
            values: { a: 'chain', b: '', c: '' },
            fieldsById: byId,
            edgesBySource: edges,
            userTouchedDest: new Set(),
            initialBlockedDest: new Set(),
        });

        expect(next.b).toBe('chain');
        expect(next.c).toBe('chain');
    });

    it('serializes through serializeFieldValue parity for text', () => {
        expect(serializeFieldValue({ type: 'text', label: '' }, '/x')).toBe(
            '/x',
        );
    });
});
