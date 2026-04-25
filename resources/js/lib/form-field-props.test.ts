import { describe, expect, it, vi } from 'vitest';
import { EMBEDDED_RELATION_DEFAULT_ACTIONS_COLUMN_ID } from '@/lib/embedded-relation-table-defaults';
import {
    mapFormFieldPropsToComponentProps,
    RELATION_TABLE_TOOLBAR_DISABLED_TITLE,
} from '@/lib/form-field-props';
import type {
    FlatpackDataTableColumn,
    FlatpackFormTableToolbarAction,
} from '@/types/data-table';
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
        expect(out.multiple).toBe(true);
    });

    it('maps relation_label_key for multi relation combobox chips', () => {
        const props: FormFieldProps = {
            type: 'combobox',
            label: 'Tags',
            options: [],
            multiple: true,
            relation: 'tags',
            relation_name: 'title',
            relation_value: 'id',
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.relationLabelKey).toBe('title');
        expect(out.useRelationRowPayload).toBe(true);
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

    it('maps bulk_actions snake_case map to bulkActions for DataTable', () => {
        const props = {
            type: 'table',
            label: 'Comments',
            relation: 'comments',
            bulk_actions: {
                remove: {
                    label: 'Delete',
                    action: 'remove',
                    variant: 'destructive',
                    confirm: true,
                    icon: 'trash',
                },
            },
            columns: [{ id: 'content', label: 'Content', type: 'text' }],
            data: [],
        } satisfies FormFieldProps;
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.bulkActions).toEqual([
            {
                id: 'remove',
                label: 'Delete',
                action: 'remove',
                variant: 'destructive',
                confirm: true,
                icon: 'trash',
            },
        ]);
    });

    it('maps table field list.yaml-style map columns to DataTable columns', () => {
        const props = {
            type: 'table',
            label: 'Comments',
            columns: {
                content: {
                    label: 'Content',
                    type: 'text',
                    searchable: true,
                    sortable: true,
                },
                user_id: {
                    label: 'User',
                    type: 'relation',
                    relation: 'user',
                    relation_name: 'name',
                    relation_value: 'id',
                    searchable: true,
                    sortable: true,
                },
            },
            data: [],
        } satisfies FormFieldProps;
        const out = mapFormFieldPropsToComponentProps(props, context);
        const cols = out.columns as FlatpackDataTableColumn[];
        expect(cols).toHaveLength(2);
        expect(cols.map((c) => c.id)).toEqual(['content', 'user_id']);
        expect(cols[1]).toMatchObject({
            id: 'user_id',
            relation: 'user',
            relationName: 'name',
            relationValue: 'id',
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
            {
                id: 'delete',
                label: 'Delete',
                action: 'delete',
                variant: 'outline',
            },
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

    it('preserves actions column with actions in table schema', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [
                {
                    id: 'actions',
                    label: 'Actions',
                    type: 'actions',
                    actions: [
                        {
                            label: 'Edit',
                            icon: 'edit',
                            action: 'edit',
                        },
                    ],
                },
            ],
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        const cols = out.columns as {
            id: string;
            type?: string;
            actions?: { action?: string }[];
        }[];
        expect(cols[0].type).toBe('actions');
        expect(cols[0].actions?.[0]?.action).toBe('edit');
    });

    it('normalizes actions map to toolbarActions', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Lines',
            columns: [{ id: 'name', label: 'Name' }],
            actions: {
                create: {
                    label: 'Create',
                    action: 'create',
                    icon: 'plus',
                    variant: 'primary',
                },
            },
            relation: 'lines',
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: null,
        });
        expect(out.toolbarActions).toEqual([
            {
                id: 'create',
                label: 'Create',
                action: 'create',
                icon: 'plus',
                variant: 'default',
            },
        ]);
        expect(out.toolbarActionsDisabled).toBe(true);
        expect(out.toolbarActionsDisabledTitle).toBe(
            'Save the parent record before using these actions.',
        );
    });

    it('does not disable toolbar when parent record exists for relation tables', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Lines',
            columns: [{ id: 'name', label: 'Name' }],
            actions: {
                create: { label: 'Create', action: 'create', icon: 'plus' },
            },
            relation: 'lines',
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: '42',
        });
        expect(out.toolbarActionsDisabled).toBeUndefined();
        expect(out.toolbarActionsDisabledTitle).toBeUndefined();
    });

    it('does not disable toolbar for non-relation tables without a parent record', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Embedded',
            columns: [{ id: 'name', label: 'Name' }],
            actions: {
                add: { label: 'Add row', action: 'add', icon: 'plus' },
            },
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: null,
        });
        const toolbarActions = out.toolbarActions as
            | FlatpackFormTableToolbarAction[]
            | undefined;
        expect(toolbarActions?.length).toBe(1);
        expect(out.toolbarActionsDisabled).toBeUndefined();
    });

    it('normalizes toolbar key when actions is omitted', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Lines',
            columns: [{ id: 'name', label: 'Name' }],
            toolbar: {
                create: {
                    label: 'Create',
                    action: 'create',
                    icon: 'plus',
                },
            },
            relation: 'lines',
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: null,
        });
        expect(
            (out.toolbarActions as FlatpackFormTableToolbarAction[] | undefined)
                ?.length,
        ).toBe(1);
        expect(
            (out.toolbarActions as FlatpackFormTableToolbarAction[])[0]?.id,
        ).toBe('create');
        expect(out.toolbarActionsDisabled).toBe(true);
        expect(out.toolbarActionsDisabledTitle).toBe(
            RELATION_TABLE_TOOLBAR_DISABLED_TITLE,
        );
    });

    it('normalizes legacy toolbar_actions when actions and toolbar are omitted', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Lines',
            columns: [{ id: 'name', label: 'Name' }],
            toolbar_actions: {
                create: {
                    label: 'Create',
                    action: 'create',
                    icon: 'plus',
                },
                add: {
                    label: 'Add',
                    action: 'add',
                    icon: 'plus',
                },
            },
            relation: 'lines',
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: null,
        });
        const toolbarActions = out.toolbarActions as
            | FlatpackFormTableToolbarAction[]
            | undefined;
        expect(toolbarActions?.length).toBe(2);
        expect(toolbarActions?.map((a) => a.id).sort()).toEqual([
            'add',
            'create',
        ]);
        expect(out.toolbarActionsDisabled).toBe(true);
    });

    it('prefers actions over toolbar when both are set', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Lines',
            columns: [{ id: 'name', label: 'Name' }],
            actions: {
                primary: {
                    label: 'From actions',
                    action: 'create',
                    icon: 'plus',
                },
            },
            toolbar: {
                ignored: {
                    label: 'From toolbar',
                    action: 'create',
                    icon: 'plus',
                },
            },
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        const toolbarActions = out.toolbarActions as
            | FlatpackFormTableToolbarAction[]
            | undefined;
        expect(toolbarActions?.length).toBe(1);
        expect(toolbarActions?.[0]?.id).toBe('primary');
        expect(toolbarActions?.[0]?.label).toBe('From actions');
    });

    it('defaults openDetailDrawerOnRowClick to true for table fields', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [{ id: 'name', label: 'Name' }],
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.rowDetailDrawer).toBe(true);
        expect(out.openDetailDrawerOnRowClick).toBe(true);
    });

    it('maps row_detail_drawer false to openDetailDrawerOnRowClick false', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [{ id: 'name', label: 'Name' }],
            data: [],
            row_detail_drawer: false,
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.rowDetailDrawer).toBe(true);
        expect(out.openDetailDrawerOnRowClick).toBe(false);
    });

    it('prefers openDetailDrawerOnRowClick over row_detail_drawer when both are set', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [{ id: 'name', label: 'Name' }],
            data: [],
            row_detail_drawer: false,
            openDetailDrawerOnRowClick: true,
        };
        const out = mapFormFieldPropsToComponentProps(props, context);
        expect(out.openDetailDrawerOnRowClick).toBe(true);
    });

    it('wires onEmbeddedTableToolbarAction to onToolbarAction for table fields', () => {
        const onEmbeddedTableToolbarAction = vi.fn();
        const props: FormFieldProps = {
            type: 'table',
            label: 'T',
            columns: [{ id: 'name', label: 'Name' }],
            data: [],
            actions: { export: { label: 'Export', action: 'export' } },
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            onEmbeddedTableToolbarAction,
        });
        const onToolbarAction = out.onToolbarAction as
            | ((id: string) => void)
            | undefined;
        expect(typeof onToolbarAction).toBe('function');
        onToolbarAction?.('export');
        expect(onEmbeddedTableToolbarAction).toHaveBeenCalledWith({
            fieldId: 'field-1',
            actionId: 'export',
        });
    });

    it('injects default relation toolbar, bulk, and row actions column when keys are omitted', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Comments',
            relation: 'comments',
            table_relation_type: 'has_many',
            columns: [{ id: 'content', label: 'Content', type: 'text' }],
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: '1',
        });
        expect(out.toolbarActions).toEqual([
            {
                id: 'create',
                label: 'Create',
                action: 'create',
                variant: 'default',
            },
        ]);
        expect(out.bulkActions).toEqual([
            {
                id: 'delete',
                label: 'Delete selected',
                action: 'delete',
                variant: 'destructive',
                confirm: true,
            },
        ]);
        const cols = out.columns as FlatpackDataTableColumn[];
        const actionsCol = cols.find(
            (c) => c.id === EMBEDDED_RELATION_DEFAULT_ACTIONS_COLUMN_ID,
        );
        expect(actionsCol?.type).toBe('actions');
        expect(actionsCol?.actions?.map((a) => a.action)).toEqual([
            'edit',
            'remove',
        ]);
    });

    it('injects create and attach for belongs_to_many', () => {
        const props: FormFieldProps = {
            type: 'table',
            label: 'Tags',
            relation: 'tags',
            table_relation_type: 'belongs_to_many',
            columns: [{ id: 'name', label: 'Name', type: 'text' }],
            data: [],
        };
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: '1',
        });
        expect(out.toolbarActions).toEqual([
            {
                id: 'create',
                label: 'Create',
                action: 'create',
                variant: 'default',
            },
            {
                id: 'attach',
                label: 'Attach',
                action: 'attach',
                variant: 'outline',
            },
        ]);
    });

    it('does not inject default toolbar when actions key is present (even if empty)', () => {
        const props = {
            type: 'table',
            label: 'T',
            relation: 'lines',
            columns: [{ id: 'x', label: 'X', type: 'text' }],
            actions: [] as unknown[],
            data: [],
        } as FormFieldProps;
        const out = mapFormFieldPropsToComponentProps(props, {
            ...context,
            parentRecordKey: '1',
        });
        expect(out.toolbarActions).toBeUndefined();
    });
});
