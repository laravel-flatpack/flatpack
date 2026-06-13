import { describe, expect, it } from 'vitest';
import { evaluateFormFieldPredicate } from '@/lib/form-action-field-predicates';

describe('evaluateFormFieldPredicate', () => {
    it('returns false when formValues context is undefined', () => {
        expect(
            evaluateFormFieldPredicate(
                'form.field_present',
                { field: 'x' },
                undefined,
            ),
        ).toBe(false);
    });

    it('form.field_eq deep-equals', () => {
        expect(
            evaluateFormFieldPredicate(
                'form.field_eq',
                { field: 'status', value: 'draft' },
                { status: 'draft' },
            ),
        ).toBe(true);
        expect(
            evaluateFormFieldPredicate(
                'form.field_eq',
                { field: 'status', value: 'draft' },
                { status: 'live' },
            ),
        ).toBe(false);
        expect(
            evaluateFormFieldPredicate(
                'form.field_eq',
                { field: 'n', value: null },
                { n: null },
            ),
        ).toBe(true);
    });

    it('form.field_in membership', () => {
        expect(
            evaluateFormFieldPredicate(
                'form.field_in',
                { field: 'status', values: ['a', 'b'] },
                { status: 'b' },
            ),
        ).toBe(true);
        expect(
            evaluateFormFieldPredicate(
                'form.field_in',
                { field: 'tags', values: ['x'] },
                { tags: ['y', 'x'] },
            ),
        ).toBe(true);
    });

    it('form.field_truthy', () => {
        expect(
            evaluateFormFieldPredicate(
                'form.field_truthy',
                { field: 't' },
                { t: '' },
            ),
        ).toBe(false);
        expect(
            evaluateFormFieldPredicate(
                'form.field_truthy',
                { field: 't' },
                { t: 'ok' },
            ),
        ).toBe(true);
        expect(
            evaluateFormFieldPredicate(
                'form.field_truthy',
                { field: 't' },
                { t: false },
            ),
        ).toBe(false);
        expect(
            evaluateFormFieldPredicate(
                'form.field_truthy',
                { field: 't' },
                { t: [] },
            ),
        ).toBe(false);
    });

    it('form.field_present and form.field_null', () => {
        expect(
            evaluateFormFieldPredicate(
                'form.field_present',
                { field: 'x' },
                {},
            ),
        ).toBe(false);
        expect(
            evaluateFormFieldPredicate('form.field_null', { field: 'x' }, {}),
        ).toBe(true);

        expect(
            evaluateFormFieldPredicate(
                'form.field_present',
                { field: 'x' },
                { x: null },
            ),
        ).toBe(false);
        expect(
            evaluateFormFieldPredicate(
                'form.field_null',
                { field: 'x' },
                { x: null },
            ),
        ).toBe(true);

        expect(
            evaluateFormFieldPredicate(
                'form.field_present',
                { field: 'x' },
                { x: 0 },
            ),
        ).toBe(true);
    });
});
