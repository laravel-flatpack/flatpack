import { describe, expect, it } from 'vitest';
import {
    buildInitialValues,
    fieldErrorMessages,
    normalizeFields,
} from '@/lib/form-schema';

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
            },
        });
        expect(buildInitialValues(fields, {})).toEqual({
            on: false,
            sw: true,
            tbl: [{ id: 1 }],
            sel: null,
            combo: null,
            multi: [],
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
});
