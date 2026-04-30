import { describe, expect, it } from 'vitest';
import { LIST_COLUMN_YAML_TYPES } from '@/lib/generated/composition-schema-keys';
import {
    listYamlColumnsToDataTableColumns,
    listYamlFiltersToDataTableFilters,
} from '@/lib/list-schema';
import type { FlatpackDataTableColumn } from '@/types/data-table';

describe('listYamlColumnsToDataTableColumns', () => {
    it('normalizes all generated list column yaml types', () => {
        const cols = listYamlColumnsToDataTableColumns(
            LIST_COLUMN_YAML_TYPES.map((type) => ({
                id: `${type}-col`,
                type,
                label: String(type),
            })),
        );

        expect(cols).toHaveLength(LIST_COLUMN_YAML_TYPES.length);
        expect(cols.map((col) => col.id)).toEqual(
            LIST_COLUMN_YAML_TYPES.map((type) => `${type}-col`),
        );
    });

    it('returns empty for null or non-object columns', () => {
        expect(listYamlColumnsToDataTableColumns(null)).toEqual([]);
        expect(listYamlColumnsToDataTableColumns('x')).toEqual([]);
    });

    it('normalizes array columns with type aliases and id', () => {
        const cols = listYamlColumnsToDataTableColumns([
            { id: 'created', type: 'datetime', label: 'Created' },
            { id: 'title', type: 'text', label: 'Title' },
        ]);
        expect(cols.find((c) => c.id === 'created')?.type).toBe('date');
        expect(cols.find((c) => c.id === 'title')?.type).toBe('text');
    });

    it('normalizes object-shaped columns using key as id fallback', () => {
        const cols = listYamlColumnsToDataTableColumns({
            name_col: { label: 'Name', type: 'text' },
        });
        expect(cols[0]?.id).toBe('name_col');
        expect(cols[0]?.label).toBe('Name');
    });

    it('maps relation snake_case keys onto column', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'owner',
                type: 'relation',
                label: 'Owner',
                relation: 'users',
                relation_name: 'name',
                relation_value: 'id',
                edit_form_field: {
                    type: 'select',
                },
            },
        ]);
        expect(cols[0]).toMatchObject({
            id: 'owner',
            relation: 'users',
            relationName: 'name',
            relationValue: 'id',
            editFormField: { type: 'select' },
        });
    });

    it('maps relation camelCase keys as compatibility aliases', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'owner',
                type: 'relation',
                label: 'Owner',
                relation: 'users',
                relationName: 'name',
                relationValue: 'id',
            },
        ]);
        expect(cols[0]).toMatchObject({
            id: 'owner',
            relation: 'users',
            relationName: 'name',
            relationValue: 'id',
        });
    });

    it('maps column editFormField override from camelCase', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'content',
                type: 'text',
                label: 'Content',
                editFormField: {
                    type: 'textarea',
                    placeholder: 'Write here',
                },
            },
        ]);
        expect(cols[0]?.editFormField).toEqual({
            type: 'textarea',
            placeholder: 'Write here',
        });
    });

    it('ignores malformed edit_form_field payloads without type', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'content',
                type: 'text',
                label: 'Content',
                edit_form_field: {
                    placeholder: 'Write here',
                },
            },
        ]);
        expect(cols[0]?.editFormField).toBeUndefined();
    });

    it('drops relation metadata when incomplete', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'owner',
                type: 'relation',
                relation: 'users',
            },
        ]);
        expect(cols[0]).not.toHaveProperty('relationName');
    });

    it('normalizes column actions and maps primary variant to default', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'actions',
                type: 'actions',
                label: 'Actions',
                actions: [
                    {
                        label: 'Edit',
                        action: 'edit',
                        icon: 'pencil',
                        variant: 'primary',
                    },
                    { label: 'Bad', action: 'a', href: '/x' },
                ],
            },
        ]);
        const actions = cols[0]?.actions;
        expect(actions?.[0]).toMatchObject({
            label: 'Edit',
            action: 'edit',
            icon: 'pencil',
            variant: 'default',
        });
        expect(actions?.length).toBe(1);
    });

    it('normalizes object-map column actions payloads', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'actions',
                type: 'actions',
                label: 'Actions',
                actions: {
                    edit: {
                        label: 'Edit',
                        action: 'edit',
                    },
                } as unknown,
            },
        ]);
        expect(cols[0]?.actions).toEqual([
            {
                label: 'Edit',
                action: 'edit',
                variant: 'outline',
            },
        ]);
    });

    it('preserves confirm and success_message on column actions', () => {
        const cols = listYamlColumnsToDataTableColumns([
            {
                id: 'actions',
                type: 'actions',
                label: 'Actions',
                actions: [
                    {
                        label: 'Delete',
                        action: 'delete',
                        variant: 'destructive',
                        confirm: true,
                        success_message: 'Removed.',
                    },
                ],
            },
        ]);
        expect(cols[0]?.actions?.[0]).toMatchObject({
            action: 'delete',
            confirm: true,
            success_message: 'Removed.',
            variant: 'destructive',
        });
    });
});

describe('listYamlFiltersToDataTableFilters', () => {
    const columns: FlatpackDataTableColumn[] = [
        {
            id: 'status',
            label: 'Status',
            type: 'select',
            options: [
                { value: 'a', label: 'Active' },
                { value: 'b', label: 'Archived' },
            ],
        },
        { id: 'created', label: 'Created', type: 'date' },
        { id: 'title', label: 'Title', type: 'text' },
    ];

    it('builds select and date filters when column exists', () => {
        const filters = listYamlFiltersToDataTableFilters(columns, {
            status: { label: 'Filter status' },
            created: { mode: 'from' },
        });
        expect(filters).toHaveLength(2);
        expect(filters[0]).toMatchObject({
            id: 'status',
            type: 'select',
            label: 'Filter status',
            options: [
                { value: 'a', label: 'Active' },
                { value: 'b', label: 'Archived' },
            ],
        });
        expect(filters[1]).toMatchObject({
            id: 'created',
            type: 'date',
            mode: 'from',
        });
    });

    it('skips unknown column ids and select without options', () => {
        const noOptions: FlatpackDataTableColumn[] = [
            { id: 'empty', label: 'E', type: 'select' },
        ];
        expect(
            listYamlFiltersToDataTableFilters(columns, {
                missing: {},
                status: {},
            }),
        ).toHaveLength(1);
        expect(
            listYamlFiltersToDataTableFilters(noOptions, { empty: {} }),
        ).toEqual([]);
    });

    it('returns empty when filters config is not an object', () => {
        expect(listYamlFiltersToDataTableFilters(columns, null)).toEqual([]);
    });

    it('uses filter-level select options when provided', () => {
        const filters = listYamlFiltersToDataTableFilters(columns, {
            status: {
                type: 'select',
                options: [
                    { value: 'draft', label: 'Draft' },
                    { value: 'live', label: 'Live' },
                ],
            },
        });

        expect(filters[0]).toMatchObject({
            id: 'status',
            options: [
                { value: 'draft', label: 'Draft' },
                { value: 'live', label: 'Live' },
            ],
        });
    });

    it('falls back to column options when filter-level options are omitted', () => {
        const filters = listYamlFiltersToDataTableFilters(columns, {
            status: { type: 'select' },
        });

        expect(filters[0]).toMatchObject({
            id: 'status',
            options: [
                { value: 'a', label: 'Active' },
                { value: 'b', label: 'Archived' },
            ],
        });
    });
});
