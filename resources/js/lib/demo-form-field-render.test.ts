import { describe, expect, it, vi } from 'vitest';
import { formFieldPropsToRenderProps } from '@/lib/demo-form-field-render';
import type { FormFieldProps } from '@/types/form-fields';

const ctx = { entryId: 'entry-1', onValueChange: vi.fn() };

describe('formFieldPropsToRenderProps', () => {
    it('maps text field props', () => {
        const props: FormFieldProps = {
            type: 'text',
            label: 'Name',
            placeholder: 'Type here',
            helperText: 'hint',
        };
        const out = formFieldPropsToRenderProps(props, ctx);
        expect(out).toMatchObject({
            id: 'entry-1',
            label: 'Name',
            placeholder: 'Type here',
            helperText: 'hint',
            onValueChange: ctx.onValueChange,
        });
        expect(out).not.toHaveProperty('type');
    });

    it('defaults placeholder for text-style fields', () => {
        const props: FormFieldProps = {
            type: 'textarea',
            label: 'T',
        };
        const out = formFieldPropsToRenderProps(props, ctx);
        expect(out.placeholder).toBe('');
    });

    it('maps checkbox without helperText in render props', () => {
        const props: FormFieldProps = {
            type: 'checkbox',
            label: 'OK',
            helperText: 'ignored',
            defaultChecked: true,
        };
        const out = formFieldPropsToRenderProps(props, ctx);
        expect(out).toMatchObject({
            id: 'entry-1',
            label: 'OK',
            defaultChecked: true,
            onValueChange: ctx.onValueChange,
        });
        expect(out).not.toHaveProperty('helperText');
    });

    it('maps combobox to items shape', () => {
        const props: FormFieldProps = {
            type: 'combobox',
            label: 'Pick',
            placeholder: 'ph',
            helperText: 'h',
            options: [
                { value: 'a', label: 'A' },
                { value: 'b', label: 'B' },
            ],
            multiple: true,
        };
        const out = formFieldPropsToRenderProps(props, ctx);
        expect(out.items).toEqual([
            { value: 'a', label: 'A' },
            { value: 'b', label: 'B' },
        ]);
        expect(out.multiItems).toEqual(['A', 'B']);
        expect(out.multiple).toBe(true);
    });

    it('maps date-picker emptyLabel from placeholder', () => {
        const props: FormFieldProps = {
            type: 'date-picker',
            label: 'Date',
            placeholder: 'Choose',
        };
        const out = formFieldPropsToRenderProps(props, ctx);
        expect(out.emptyLabel).toBe('Choose');
    });

    it('maps time-picker with defaults', () => {
        const props: FormFieldProps = {
            type: 'time-picker',
            label: 'When',
            placeholder: 'p',
        };
        const out = formFieldPropsToRenderProps(props, ctx);
        expect(out).toMatchObject({
            dateLabel: 'When',
            timeLabel: 'Time',
            dateEmptyLabel: 'p',
            timeDefaultValue: '09:00:00',
        });
    });
});
