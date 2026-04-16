import { describe, expect, it, vi } from 'vitest';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import type { FormFieldProps } from '@/types/form-fields';

const context = { fieldId: 'field-1', onValueChange: vi.fn() };

describe('mapFormFieldPropsToComponentProps', () => {
    it('maps text field props', () => {
        const props: FormFieldProps = {
            type: 'text',
            label: 'Name',
            placeholder: 'Type here',
            helperText: 'hint',
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out).toMatchObject({
            id: 'field-1',
            label: 'Name',
            placeholder: 'Type here',
            helperText: 'hint',
            onValueChange: context.onValueChange,
        });
        expect(out).not.toHaveProperty('type');
    });

    it('defaults placeholder for text-style fields', () => {
        const props: FormFieldProps = {
            type: 'textarea',
            label: 'T',
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.placeholder).toBe('');
    });

    it('forwards rows for textarea', () => {
        const props: FormFieldProps = {
            type: 'textarea',
            label: 'T',
            placeholder: 'p',
            rows: 10,
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.rows).toBe(10);
    });

    it('maps checkbox without helperText in component props', () => {
        const props: FormFieldProps = {
            type: 'checkbox',
            label: 'OK',
            helperText: 'ignored',
            defaultChecked: true,
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out).toMatchObject({
            id: 'field-1',
            label: 'OK',
            defaultChecked: true,
            onValueChange: context.onValueChange,
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
        const out = mapFormFieldPropsToComponentProps(props, context);
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
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.emptyLabel).toBe('Choose');
    });

    it('maps time-picker with defaults', () => {
        const props: FormFieldProps = {
            type: 'time-picker',
            label: 'When',
            placeholder: 'p',
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out).toMatchObject({
            dateLabel: 'When',
            timeLabel: 'Time',
            dateEmptyLabel: 'p',
            timeDefaultValue: '09:00:00',
        });
    });

    it('maps table field with columns and row data', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Users',
            columns: [
                { id: 'name', label: 'Name', sortable: true },
                {
                    id: 'status',
                    label: 'Status',
                    type: 'select',
                    options: [{ value: 'a', label: 'Active' }],
                },
            ],
            data: [{ name: 'Ada', status: 'a' }],
            bulkActions: [{ id: 'delete', label: 'Delete', action: 'delete' }],
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.id).toBe('field-1');
        expect(out.columns).toHaveLength(2);
        expect(out.data).toEqual([{ name: 'Ada', status: 'a' }]);
        expect(out.bulkActions).toEqual([
            { id: 'delete', label: 'Delete', action: 'delete' },
        ]);
    });

    it('defaults table data to empty array when omitted', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [{ id: 'x', label: 'X' }],
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.data).toEqual([]);
    });

    it('preserves actions column with buttons in table schema', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [
                {
                    id: 'actions',
                    label: 'Actions',
                    type: 'actions',
                    buttons: {
                        edit: {
                            label: 'Edit',
                            icon: 'edit',
                            href: '/{id}/edit',
                        },
                    },
                },
            ],
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        const cols = out.columns as {
            id: string;
            type?: string;
            buttons?: object;
        }[];
        expect(cols[0].type).toBe('actions');
        expect(cols[0].buttons).toHaveProperty('edit');
    });
});
