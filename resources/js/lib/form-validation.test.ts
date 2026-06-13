import { describe, expect, it } from 'vitest';
import {
    clientValidationErrors,
    type FormValidationFieldEntry,
    fieldIsRequired,
} from '@/lib/form-validation';
import type { FormFieldProps } from '@/types/form-fields';

function entry(id: string, field: FormFieldProps): FormValidationFieldEntry {
    return { id, field };
}

describe('fieldIsRequired', () => {
    it('is true only when required flag is strictly true', () => {
        expect(
            fieldIsRequired({
                type: 'text',
                label: 'A',
                required: true,
            } as FormFieldProps),
        ).toBe(true);
        expect(
            fieldIsRequired({
                type: 'text',
                label: 'A',
                required: false,
            } as FormFieldProps),
        ).toBe(false);
        expect(fieldIsRequired({ type: 'text', label: 'A' })).toBe(false);
    });
});

describe('clientValidationErrors', () => {
    it('returns empty when no required fields', () => {
        const errors = clientValidationErrors(
            [entry('a', { type: 'text', label: 'A' })],
            { a: '' },
        );
        expect(errors).toEqual({});
    });

    it('flags missing required text with label', () => {
        const errors = clientValidationErrors(
            [
                entry('name', {
                    type: 'text',
                    label: 'Name',
                    required: true,
                } as FormFieldProps),
            ],
            { name: '' },
        );
        expect(errors).toEqual({ name: 'Name is required.' });
    });

    it('uses field id when label is blank', () => {
        const errors = clientValidationErrors(
            [
                entry('slug', {
                    type: 'text',
                    label: '   ',
                    required: true,
                } as FormFieldProps),
            ],
            { slug: null },
        );
        expect(errors).toEqual({ slug: 'slug is required.' });
    });

    it('treats whitespace-only string as empty', () => {
        const errors = clientValidationErrors(
            [
                entry('x', {
                    type: 'text',
                    label: 'X',
                    required: true,
                } as FormFieldProps),
            ],
            { x: '   ' },
        );
        expect(errors.x).toBeDefined();
    });

    it('treats empty array as empty for required combobox', () => {
        const errors = clientValidationErrors(
            [
                entry('tags', {
                    type: 'combobox',
                    label: 'Tags',
                    options: [],
                    required: true,
                    multiple: true,
                } as FormFieldProps),
            ],
            { tags: [] },
        );
        expect(errors.tags).toBe('Tags is required.');
    });

    it('treats empty plain object as empty', () => {
        const errors = clientValidationErrors(
            [
                entry('meta', {
                    type: 'text',
                    label: 'Meta',
                    required: true,
                } as FormFieldProps),
            ],
            { meta: {} },
        );
        expect(errors.meta).toBeDefined();
    });

    it('treats invalid Date as empty', () => {
        const errors = clientValidationErrors(
            [
                entry('d', {
                    type: 'date-picker',
                    label: 'Date',
                    required: true,
                } as FormFieldProps),
            ],
            { d: new Date(Number.NaN) },
        );
        expect(errors.d).toBe('Date is required.');
    });

    it('unwraps nested { value } for emptiness', () => {
        const errors = clientValidationErrors(
            [
                entry('w', {
                    type: 'text',
                    label: 'W',
                    required: true,
                } as FormFieldProps),
            ],
            { w: { value: '' } },
        );
        expect(errors.w).toBe('W is required.');
    });

    it('skips when value is present', () => {
        expect(
            clientValidationErrors(
                [
                    entry('a', {
                        type: 'text',
                        label: 'A',
                        required: true,
                    } as FormFieldProps),
                ],
                { a: 'ok' },
            ),
        ).toEqual({});
    });
});
