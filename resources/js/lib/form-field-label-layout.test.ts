import { describe, expect, it } from 'vitest';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';

describe('resolveFormFieldLabelLayout', () => {
    it('defaults stacked axis to vertical when showLabel omitted', () => {
        expect(resolveFormFieldLabelLayout(undefined, 'stacked')).toEqual({
            orientation: 'vertical',
        });
    });

    it('defaults inline axis to horizontal when showLabel omitted', () => {
        expect(resolveFormFieldLabelLayout(undefined, 'inline')).toEqual({
            orientation: 'horizontal',
        });
    });

    it('forces horizontal for inline', () => {
        expect(resolveFormFieldLabelLayout('inline', 'stacked')).toEqual({
            orientation: 'horizontal',
        });
    });

    it('forces vertical for stacked', () => {
        expect(resolveFormFieldLabelLayout('stacked', 'inline')).toEqual({
            orientation: 'vertical',
        });
    });

    it('uses sr-only for none with stacked default', () => {
        expect(resolveFormFieldLabelLayout('none', 'stacked')).toEqual({
            orientation: 'vertical',
            labelClassName: 'sr-only',
        });
    });

    it('uses sr-only for none with inline default', () => {
        expect(resolveFormFieldLabelLayout('none', 'inline')).toEqual({
            orientation: 'horizontal',
            labelClassName: 'sr-only',
        });
    });
});
