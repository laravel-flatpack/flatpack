import { describe, expect, it } from 'vitest';
import {
    buildInitialValues,
    fieldErrorMessages,
    fieldHasValidationError,
    firstVisibleFieldEntryWithValidationError,
    normalizeFields,
    validationErrorsFingerprint,
} from '@/lib/form-schema';
import { FORM_FIELD_TYPES_CANONICAL } from '@/lib/generated/composition-schema-keys';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

describe('normalizeFields', () => {
    it('normalizes every canonical generated form field type', () => {
        const fields = Object.fromEntries(
            FORM_FIELD_TYPES_CANONICAL.map((type) => [
                type,
                { id: type, type, label: String(type) },
            ]),
        );
        const entries = normalizeFields({ fields });

        expect(entries).toHaveLength(FORM_FIELD_TYPES_CANONICAL.length);
        expect(entries.map((entry) => entry.field.type).sort()).toEqual(
            [...FORM_FIELD_TYPES_CANONICAL].sort(),
        );
    });

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

    it('ignores tabs when fields are missing', () => {
        const entries = normalizeFields({
            tabs: {
                one: {
                    label: 'One',
                    fields: { alpha: { type: 'text', label: 'Alpha' } },
                },
            },
        });
        expect(entries).toEqual([]);
    });

    it('does not merge tabs into fields; only reads schema.fields', () => {
        const entries = normalizeFields({
            fields: {
                a: { id: 'a', type: 'text', label: 'A' },
            },
            tabs: {
                one: {
                    label: 'One',
                    fields: { b: { id: 'b', type: 'text', label: 'B' } },
                },
            },
        });
        expect(entries.map((e) => e.id)).toEqual(['a']);
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
