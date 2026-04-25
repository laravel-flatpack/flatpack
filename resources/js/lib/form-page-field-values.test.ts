import { describe, expect, it, vi } from 'vitest';
import {
    componentValueProps,
    relationRemoteProps,
    serializeFieldValue,
} from '@/lib/form-page-field-values';
import type { FormFieldProps } from '@/types/form-fields';

const hoisted = vi.hoisted(() => ({
    route: vi.fn((name: string, params?: { entity?: string }) => {
        if (name === 'flatpack.entities.relation-options') {
            return `/flatpack/${params?.entity ?? 'e'}/relation-options`;
        }
        return '/';
    }),
}));

vi.mock('@/lib/route', () => ({
    route: hoisted.route,
}));

describe('serializeFieldValue', () => {
    it('serializes date-picker to YYYY-MM-DD or null', () => {
        const field: FormFieldProps = { type: 'date-picker', label: 'D' };
        const d = new Date(2024, 5, 7);
        expect(serializeFieldValue(field, d)).toBe('2024-06-07');
        expect(serializeFieldValue(field, 'not-a-date')).toBeNull();
    });

    it('serializes date-range-picker', () => {
        const field: FormFieldProps = {
            type: 'date-range-picker',
            label: 'R',
        };
        expect(
            serializeFieldValue(field, {
                from: new Date(2024, 0, 1),
                to: new Date(2024, 0, 31),
            }),
        ).toEqual({ from: '2024-01-01', to: '2024-01-31' });
        expect(serializeFieldValue(field, { not: 'object' })).toBeNull();
    });

    it('serializes time-picker', () => {
        const field: FormFieldProps = { type: 'time-picker', label: 'T' };
        expect(
            serializeFieldValue(field, {
                date: new Date(2024, 2, 10),
                time: '12:30:00',
            }),
        ).toEqual({ date: '2024-03-10', time: '12:30:00' });
    });

    it('passes through for other field types', () => {
        const field: FormFieldProps = { type: 'text', label: 'X' };
        expect(serializeFieldValue(field, 'hello')).toBe('hello');
    });
});

describe('componentValueProps', () => {
    it('maps text-like fields to string value', () => {
        expect(componentValueProps({ type: 'text', label: 'T' }, null)).toEqual(
            { value: '' },
        );
        expect(
            componentValueProps({ type: 'textarea', label: 'T' }, 42),
        ).toEqual({ value: '42' });
    });

    it('maps checkbox and switch to checked', () => {
        expect(
            componentValueProps({ type: 'checkbox', label: 'C' }, true),
        ).toEqual({ checked: true });
        expect(
            componentValueProps({ type: 'switch', label: 'S' }, false),
        ).toEqual({ checked: false });
    });

    it('parses date-picker from ISO date string', () => {
        const d = componentValueProps(
            { type: 'date-picker', label: 'D' },
            '2024-08-20',
        ).value as Date | undefined;
        expect(d).toBeInstanceOf(Date);
        expect(d?.getFullYear()).toBe(2024);
        expect(d?.getMonth()).toBe(7);
        expect(d?.getDate()).toBe(20);
    });

    it('parses date-picker from datetime-like string using local date segment', () => {
        const d = componentValueProps(
            { type: 'date-picker', label: 'D' },
            '2026-04-25 14:30:00',
        ).value as Date | undefined;
        expect(d).toBeInstanceOf(Date);
        expect(d?.getFullYear()).toBe(2026);
        expect(d?.getMonth()).toBe(3);
        expect(d?.getDate()).toBe(25);
    });

    it('parses date-range from serialized object', () => {
        const v = componentValueProps(
            { type: 'date-range-picker', label: 'R' },
            { from: '2024-01-01', to: '2024-01-02' },
        ).value as { from?: Date; to?: Date } | undefined;
        expect(v?.from?.getFullYear()).toBe(2024);
        expect(v?.to?.getMonth()).toBe(0);
    });

    it('passes table data or field.data default', () => {
        expect(
            componentValueProps(
                {
                    type: 'table',
                    label: 'T',
                    columns: [{ id: 'x', label: 'X' }],
                    data: [{ x: 1 }],
                },
                [{ x: 2 }],
            ),
        ).toEqual({ data: [{ x: 2 }] });
        expect(
            componentValueProps(
                {
                    type: 'table',
                    label: 'T',
                    columns: [{ id: 'x', label: 'X' }],
                    data: [{ x: 1 }],
                },
                'not-array',
            ),
        ).toEqual({ data: [{ x: 1 }] });
    });
});

describe('relationRemoteProps', () => {
    it('returns empty for non-combobox or missing relation', () => {
        expect(
            relationRemoteProps({ type: 'text', label: 'T' }, 'f1', 'posts'),
        ).toEqual({});
        expect(
            relationRemoteProps(
                { type: 'combobox', label: 'C', options: [] } as FormFieldProps,
                'f1',
                'posts',
            ),
        ).toEqual({});
    });

    it('includes remote endpoint when relation is set', () => {
        const props = relationRemoteProps(
            {
                type: 'combobox',
                label: 'Author',
                options: [],
                relation: 'user',
                remote: true,
            } as FormFieldProps,
            'author_id',
            'posts',
        );
        expect(props).toEqual({
            remote: true,
            remoteEndpoint: '/flatpack/posts/relation-options',
            remoteFieldId: 'author_id',
        });
        expect(hoisted.route).toHaveBeenCalledWith(
            'flatpack.entities.relation-options',
            { entity: 'posts' },
        );
    });
});
