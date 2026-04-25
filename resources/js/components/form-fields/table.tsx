import type { ReactNode } from 'react';
import { DataTable } from '@/components/table/data-table';
import type {
    DataTableRowDrawerAttachBodyRenderContext,
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackFormTableToolbarAction,
} from '@/types/data-table';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';

export const TableField = ({
    id,
    label,
    helperText,
    columns,
    data,
    bulkActions,
    toolbarActions,
    toolbarActionsDisabled,
    toolbarActionsDisabledTitle,
    onToolbarAction,
    rowDetailDrawer = true,
    openDetailDrawerOnRowClick = true,
    reorderable,
    onValueChange,
    renderRowDrawerAttachBody,
}: {
    id: string;
    label: string;
    helperText?: string;
    columns: FlatpackDataTableColumn[];
    data: Record<string, unknown>[];
    bulkActions?: FlatpackDataTableBulkAction[];
    toolbarActions?: FlatpackFormTableToolbarAction[];
    toolbarActionsDisabled?: boolean;
    toolbarActionsDisabledTitle?: string;
    onToolbarAction?: (actionId: string) => void;
    /** Enables the row detail drawer (toolbar `create` draft flow and optional row-click open). */
    rowDetailDrawer?: boolean;
    /** When false while `rowDetailDrawer` is true, row clicks do not open the drawer. */
    openDetailDrawerOnRowClick?: boolean;
    reorderable?: boolean | string;
    onValueChange?: (value: unknown) => void;
    renderRowDrawerAttachBody?: (
        ctx: DataTableRowDrawerAttachBodyRenderContext,
    ) => ReactNode;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <DataTable
                    id={id}
                    columns={columns}
                    data={data}
                    bulkActions={bulkActions}
                    toolbarActions={toolbarActions}
                    toolbarActionsDisabled={toolbarActionsDisabled}
                    toolbarActionsDisabledTitle={toolbarActionsDisabledTitle}
                    onToolbarAction={onToolbarAction}
                    reorderable={reorderable}
                    rowDetailDrawer={rowDetailDrawer}
                    openDetailDrawerOnRowClick={openDetailDrawerOnRowClick}
                    onValueChange={onValueChange}
                    renderRowDrawerAttachBody={renderRowDrawerAttachBody}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
