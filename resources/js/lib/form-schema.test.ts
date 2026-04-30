import { describe, expect, it } from 'vitest';
import {
    buildInitialValues,
    type FlatpackFormTabPanelLayout,
    fieldErrorMessages,
    fieldHasValidationError,
    firstVisibleFieldEntryWithValidationError,
    mergeFormTabsIntoSchemaFields,
    normalizeFields,
    validationErrorsFingerprint,
} from '@/lib/form-schema';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

describe('mergeFormTabsIntoSchemaFields', () => {
    it('merges tab fields into flat fields and builds tab_panels', () => {
        const out = mergeFormTabsIntoSchemaFields({
            tabs: {
                profile: {
                    label: 'Profile',
                    fields: { name: { type: 'text', label: 'Name' } },
                },
                settings: {
                    label: 'Settings',
                    icon: 'cog',
                    fields: { status: { type: 'text', label: 'Status' } },
                },
            },
        });
        expect(out.tabs).toBeUndefined();
        expect(out.tab_panels).toHaveLength(2);
        expect(out.fields).toMatchObject({
            name: expect.objectContaining({ type: 'text' }),
            status: expect.objectContaining({ type: 'text' }),
        });
        const ids = normalizeFields(out as Record<string, unknown>)
            .map((e) => e.id)
            .sort();
        expect(ids).toEqual(['name', 'status']);
    });

    it('merges top-level fields then tab fields into one map', () => {
        const out = mergeFormTabsIntoSchemaFields({
            fields: {
                title: { type: 'text', label: 'Title' },
            },
            tabs: {
                body: {
                    label: 'Body',
                    fields: {
                        content: { type: 'textarea', label: 'Content' },
                    },
                },
            },
        });
        expect(out.fields).toMatchObject({
            title: expect.objectContaining({ type: 'text' }),
            content: expect.objectContaining({ type: 'textarea' }),
        });
        const tabPanels = out.tab_panels as
            | FlatpackFormTabPanelLayout[]
            | undefined;
        expect(tabPanels).toHaveLength(1);
        expect(tabPanels?.[0]?.field_ids).toEqual(['content']);
    });
});

describe('normalizeFields', () => {
    it('returns empty array when schema is nullish or fields missing', () => {
        expect(normalizeFields(null)).toEqual([]);
        expect(normalizeFields(undefined)).toEqual([]);
        expect(normalizeFields({})).toEqual([]);
        expect(normalizeFields({ fields: 'bad' })).toEqual([]);
    });

    it('maps date alias to date-picker and keeps combobox with relation', () => {
        const entries = normalizeFields({
            fields: {
                a: { id: 'a', type: 'date' },
                b: {
                    id: 'b',
                    type: 'combobox',
                    relation: 'category',
                },
            },
        });
        expect(entries.map((e) => [e.id, e.field.type])).toEqual([
            ['a', 'date-picker'],
            ['b', 'combobox'],
        ]);
    });

    it('drops type relation (use combobox with relation in form.yaml)', () => {
        const entries = normalizeFields({
            fields: {
                legacy: {
                    id: 'legacy',
                    type: 'relation',
                    relation: 'category',
                },
            },
        });
        expect(entries).toEqual([]);
    });

    it('drops entries with unknown type or empty id', () => {
        expect(
            normalizeFields({
                fields: {
                    x: { id: 'x', type: 'not-a-field' },
                    y: { id: '   ', type: 'text' },
                },
            }),
        ).toEqual([]);
    });

    it('uses field id with fallback to key', () => {
        const [only] = normalizeFields({
            fields: {
                fallback: { type: 'text' },
            },
        });
        expect(only?.id).toBe('fallback');
    });

    it('merges tabs-only schema into field entries', () => {
        const entries = normalizeFields({
            tabs: {
                one: {
                    label: 'One',
                    fields: { alpha: { type: 'text', label: 'Alpha' } },
                },
            },
        });
        expect(entries.map((e) => e.id)).toEqual(['alpha']);
    });
});

describe('buildInitialValues', () => {
    it('prefers explicit values bag over defaults', () => {
        const fields = normalizeFields({
            fields: {
                title: { id: 'title', type: 'text' },
            },
        });
        expect(buildInitialValues(fields, { title: 'From server' })).toEqual({
            title: 'From server',
        });
    });

    it('uses field.value when present and key missing', () => {
        const fields = normalizeFields({
            fields: {
                code: { id: 'code', type: 'text', value: 'preset' },
            },
        });
        expect(buildInitialValues(fields, {})).toEqual({ code: 'preset' });
    });

    it('applies type-specific defaults', () => {
        const fields = normalizeFields({
            fields: {
                on: { id: 'on', type: 'checkbox' },
                sw: { id: 'sw', type: 'switch', defaultChecked: true },
                tbl: { id: 'tbl', type: 'table', data: [{ id: 1 }] },
                sel: { id: 'sel', type: 'select' },
                combo: { id: 'combo', type: 'combobox' },
                multi: { id: 'multi', type: 'combobox', multiple: true },
                avatar: { id: 'avatar', type: 'file-upload', mode: 'url' },
                docs: {
                    id: 'docs',
                    type: 'file-upload',
                    mode: 'relation',
                    multiple: true,
                },
            },
        });
        expect(buildInitialValues(fields, {})).toEqual({
            on: false,
            sw: true,
            tbl: [{ id: 1 }],
            sel: null,
            combo: null,
            multi: [],
            avatar: null,
            docs: [],
        });
    });

    it('normalizes date-picker initial values to YYYY-MM-DD', () => {
        const fields = normalizeFields({
            fields: {
                published_at: {
                    id: 'published_at',
                    type: 'date-picker',
                },
            },
        });

        expect(
            buildInitialValues(fields, {
                published_at: '2026-04-25 14:30:00',
            }),
        ).toEqual({
            published_at: '2026-04-25',
        });
    });
});

describe('fieldErrorMessages', () => {
    it('maps string and array errors to message objects', () => {
        expect(fieldErrorMessages({ a: 'One' }, 'a')).toEqual([
            { message: 'One' },
        ]);
        expect(fieldErrorMessages({ b: ['', 'Two', 'Three'] }, 'b')).toEqual([
            { message: 'Two' },
            { message: 'Three' },
        ]);
    });

    it('returns empty for missing or blank errors', () => {
        expect(fieldErrorMessages({}, 'x')).toEqual([]);
        expect(fieldErrorMessages({ x: '  ' }, 'x')).toEqual([]);
        expect(fieldErrorMessages({ x: ['', '  '] }, 'x')).toEqual([]);
    });

    it('reads Laravel-style values.field keys', () => {
        expect(
            fieldErrorMessages({ 'values.title': 'Required.' }, 'title'),
        ).toEqual([{ message: 'Required.' }]);
    });
});

describe('validationErrorsFingerprint', () => {
    it('ignores flatpack and sorts keys', () => {
        expect(
            validationErrorsFingerprint({
                flatpack: 'x',
                b: '2',
                a: '1',
            }),
        ).toBe('a\0b');
    });
});

describe('firstVisibleFieldEntryWithValidationError', () => {
    it('returns first visible entry in order with an error', () => {
        const ordered: SchemaFieldRenderEntry[] = [
            {
                id: 'title',
                field: { type: 'text', label: 'T' },
                value: '',
                onValueChange: () => {},
                hidden: false,
            },
            {
                id: 'slug',
                field: { type: 'text', label: 'S' },
                value: '',
                onValueChange: () => {},
                hidden: false,
            },
        ];
        const hit = firstVisibleFieldEntryWithValidationError(ordered, {
            'values.slug': 'Bad',
        });
        expect(hit?.id).toBe('slug');
    });

    it('skips hidden entries', () => {
        const ordered: SchemaFieldRenderEntry[] = [
            {
                id: 'a',
                field: { type: 'text', label: 'A' },
                value: '',
                onValueChange: () => {},
                hidden: true,
            },
            {
                id: 'b',
                field: { type: 'text', label: 'B' },
                value: '',
                onValueChange: () => {},
                hidden: false,
            },
        ];
        const hit = firstVisibleFieldEntryWithValidationError(ordered, {
            a: 'Err',
            b: 'Err2',
        });
        expect(hit?.id).toBe('b');
    });
});

describe('fieldHasValidationError', () => {
    it('detects table row errors from nested keys', () => {
        const field = {
            type: 'table' as const,
            label: 'C',
            columns: [],
            relation: 'comments',
        };
        expect(
            fieldHasValidationError(
                {
                    'values.comments.0.content': 'Required.',
                },
                'comments',
                field,
            ),
        ).toBe(true);
    });
});
