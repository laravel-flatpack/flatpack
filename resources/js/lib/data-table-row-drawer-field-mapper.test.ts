import { describe, expect, it } from 'vitest';
import { mapDataTableColumnToDrawerField } from '@/lib/data-table-row-drawer-field-mapper';
import type { FlatpackDataTableColumn } from '@/types/data-table';

describe('mapDataTableColumnToDrawerField', () => {
    it('maps select column to select field', () => {
        const col: FlatpackDataTableColumn = {
            id: 'status',
            label: 'Status',
            type: 'select',
            options: [{ value: 'draft', label: 'Draft' }],
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'select',
                label: 'Status',
                placeholder: 'Choose...',
                options: [{ value: 'draft', label: 'Draft' }],
            },
        });
    });

    it('maps date column to date-picker field', () => {
        const col: FlatpackDataTableColumn = {
            id: 'published_at',
            label: 'Published',
            type: 'date',
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'date-picker',
                label: 'Published',
                placeholder: 'Pick a date...',
            },
        });
    });

    it('uses edit_form_field override when provided', () => {
        const col: FlatpackDataTableColumn = {
            id: 'content',
            label: 'Content',
            type: 'text',
            editFormField: {
                type: 'textarea',
                placeholder: 'Insert...',
            },
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'textarea',
                label: 'Content',
                placeholder: 'Insert...',
            },
        });
    });

    it('returns null when edit_form_field type is table', () => {
        const col: FlatpackDataTableColumn = {
            id: 'child_rows',
            label: 'Child Rows',
            type: 'text',
            editFormField: {
                type: 'table',
            },
        };
        expect(mapDataTableColumnToDrawerField(col)).toBeNull();
    });

    it('supports heavy non-table override types', () => {
        const col: FlatpackDataTableColumn = {
            id: 'body',
            label: 'Body',
            type: 'text',
            editFormField: {
                type: 'rich-text',
                placeholder: 'Write body',
            },
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'rich-text',
                label: 'Body',
                placeholder: 'Write body',
            },
        });
    });

    it('maps relation to generic combobox field contract', () => {
        const col: FlatpackDataTableColumn = {
            id: 'user_id',
            label: 'User',
            type: 'relation',
            relation: 'user',
            relationName: 'name',
            relationValue: 'id',
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'combobox',
                label: 'User',
                placeholder: 'Choose...',
                options: [],
                multiple: false,
                relation: 'user',
                relation_name: 'name',
                relation_value: 'id',
                remote: true,
                emitObject: true,
            },
        });
    });

    it('lets edit_form_field override relation combobox props', () => {
        const col: FlatpackDataTableColumn = {
            id: 'user_id',
            label: 'User',
            type: 'relation',
            relation: 'user',
            relationName: 'name',
            relationValue: 'id',
            editFormField: {
                type: 'combobox',
                placeholder: 'Pick one',
                remote: false,
            },
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'combobox',
                label: 'User',
                placeholder: 'Pick one',
                options: [],
                relation: 'user',
                relation_name: 'name',
                relation_value: 'id',
                remote: false,
                emitObject: true,
            },
        });
    });

    it('ignores malformed edit_form_field override payloads', () => {
        const col: FlatpackDataTableColumn = {
            id: 'status',
            label: 'Status',
            type: 'text',
            editFormField: {
                type: 'select',
                // Invalid override shape: options must be an array when present.
                options: 'bad' as unknown as never,
            },
        };
        expect(mapDataTableColumnToDrawerField(col)).toEqual({
            kind: 'form',
            field: {
                type: 'select',
                label: 'Status',
                placeholder: 'Choose...',
                options: [],
            },
        });
    });
});
