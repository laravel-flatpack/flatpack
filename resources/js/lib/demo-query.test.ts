import { describe, expect, it } from 'vitest';
import {
    flattenDemoQuery,
    isDemoCatalogEmbedMode,
    mergeDemoFlatQuery,
    mergeQueryOverridesIntoFormFieldProps,
    parseLocationSearch,
    parseSearchParamsFromUrl,
    pickDemoComponentSelector,
    resolveDemoShowValue,
} from '@/lib/demo-query';
import type { DemoComponentsInertiaProps } from '@/types/demo';
import type { FormFieldProps } from '@/types/form-fields';

describe('parseSearchParamsFromUrl', () => {
    it('parses query string (last value wins)', () => {
        expect(parseSearchParamsFromUrl('/p?a=1&a=2&b=x')).toEqual({
            a: '2',
            b: 'x',
        });
    });

    it('returns empty object on invalid input', () => {
        expect(parseSearchParamsFromUrl(':::')).toEqual({});
    });
});

describe('parseLocationSearch', () => {
    it('returns empty for empty or bare ?', () => {
        expect(parseLocationSearch('')).toEqual({});
        expect(parseLocationSearch('?')).toEqual({});
    });

    it('accepts search with or without leading ?', () => {
        expect(parseLocationSearch('?foo=bar')).toEqual({ foo: 'bar' });
        expect(parseLocationSearch('foo=bar')).toEqual({ foo: 'bar' });
    });
});

describe('pickDemoComponentSelector', () => {
    it('returns first non-empty routing key in priority order', () => {
        expect(
            pickDemoComponentSelector({
                type: '',
                demo: '  text  ',
                field: 'other',
            }),
        ).toBe('text');
    });

    it('returns empty string when no routing key set', () => {
        expect(pickDemoComponentSelector({ label: 'x' })).toBe('');
    });
});

describe('flattenDemoQuery', () => {
    it('normalizes Laravel-style maps to string values', () => {
        expect(
            flattenDemoQuery({
                a: 1,
                b: ['x', 'y'],
                skip: null,
            }),
        ).toEqual({ a: '1', b: 'y' });
    });

    it('returns empty for null, array root, or non-object', () => {
        expect(flattenDemoQuery(null)).toEqual({});
        expect(flattenDemoQuery(undefined)).toEqual({});
        expect(
            flattenDemoQuery([] as unknown as Record<string, unknown>),
        ).toEqual({});
    });
});

describe('mergeDemoFlatQuery', () => {
    it('merges with address bar winning over inertia URL over server', () => {
        expect(
            mergeDemoFlatQuery({
                query: { a: 'server', b: 'server-b' },
                inertiaUrl: '/?a=inertia&c=inertia-c',
                locationSearch: '?a=bar&b=browser',
            }),
        ).toEqual({
            a: 'bar',
            b: 'browser',
            c: 'inertia-c',
        });
    });
});

describe('mergeQueryOverridesIntoFormFieldProps', () => {
    const base: FormFieldProps = {
        type: 'text',
        label: 'L',
        placeholder: 'P',
    };

    it('merges allowed keys and coerces booleans', () => {
        const out = mergeQueryOverridesIntoFormFieldProps(base, {
            label: 'From URL',
            placeholder: 'still',
            type: 'textarea',
            unknown: 'x',
            defaultChecked: 'true',
        });
        expect(out.type).toBe('text');
        expect(out.label).toBe('From URL');
        expect('unknown' in out).toBe(false);
    });

    it('ignores routing keys', () => {
        const out = mergeQueryOverridesIntoFormFieldProps(base, {
            type: 'checkbox',
            demo: 'nope',
        });
        expect(out.type).toBe('text');
    });
});

describe('isDemoCatalogEmbedMode', () => {
    const baseProps = (): DemoComponentsInertiaProps => ({
        catalogId: 'all',
        query: {},
        document: {
            id: 'all',
            title: 'Components',
            description: null,
            meta: { fieldCount: 0, widgetCount: 0 },
            fields: [],
            widgets: [],
        },
    });

    it('is true when a routing selector is present', () => {
        expect(isDemoCatalogEmbedMode(baseProps(), '/demo?type=text', '')).toBe(
            true,
        );
    });

    it('is false when only prop overrides or unrelated keys are present', () => {
        expect(
            isDemoCatalogEmbedMode(
                { ...baseProps(), query: { label: 'x' } },
                '/demo',
                '',
            ),
        ).toBe(false);
    });
});

describe('resolveDemoShowValue', () => {
    const entry = { showValue: true };

    it('uses catalog default when showValue key absent', () => {
        expect(resolveDemoShowValue(entry, {})).toBe(true);
        expect(resolveDemoShowValue({ showValue: false }, {})).toBe(false);
    });

    it('overrides when showValue is coerced to boolean', () => {
        expect(resolveDemoShowValue(entry, { showValue: 'false' })).toBe(false);
        expect(
            resolveDemoShowValue({ showValue: false }, { showValue: '1' }),
        ).toBe(true);
    });

    it('falls back to catalog when value is not boolean', () => {
        expect(resolveDemoShowValue(entry, { showValue: 'maybe' })).toBe(true);
    });
});
