/**
 * Data table layout shell. Table behavior and invariants: `useDataTableController`.
 */
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { DATA_TABLE_LABEL } from '@/components/table/data-table-constants';
import { DataTableRowDrawerPanel } from '@/components/table/data-table-row-drawer';
import { DataTableToolbar } from '@/components/table/data-table-toolbar';
import { useDataTableController } from '@/hooks/use-data-table-controller';
import { cn } from '@/lib/utils';
import type { DataTableProps } from '@/types/data-table';

export { buildDataTableColumnDefs } from '@/components/table/data-table-column-defs';
export type { DataTableController } from '@/hooks/use-data-table-controller';
export { useDataTableController } from '@/hooks/use-data-table-controller';
export type {
    DataTableProps,
    DataTableRowDrawerAttachBodyRenderContext,
    DataTableRowDrawerBodyVariant,
    FlatpackListServerPagination,
    FlatpackTableRelationType,
} from '@/types/data-table';

export function DataTable(props: DataTableProps) {
    const {
        className,
        tableRelationType,
        flatpackEntity,
        flatpackTableFieldId,
        ...tableProps
    } = props;
    const c = useDataTableController(tableProps);

    return (
        <div
            className={cn('flex w-full flex-col gap-4', className)}
            role="region"
            aria-labelledby={c.tableLabelId}
            {...(tableRelationType !== undefined
                ? {
                      'data-flatpack-table-relation-type': tableRelationType,
                  }
                : {})}
        >
            <span id={c.tableLabelId} className="sr-only">
                {DATA_TABLE_LABEL}
            </span>
            <DataTableToolbar
                id={c.id}
                table={c.table}
                hasToolbarActions={c.hasToolbarActions}
                toolbarActions={c.toolbarActions}
                onToolbarAction={c.handleToolbarActionClick}
                toolbarActionsDisabled={c.toolbarActionsDisabled}
                toolbarActionsDisabledTitle={c.toolbarActionsDisabledTitle}
                hasBulkActions={c.hasBulkActions}
                selectedRowCount={c.selectedRowCount}
                isAllRowsSelected={c.isAllRowsSelected}
                totalRowCount={c.totalRowCount}
                onSelectAllRows={c.handleSelectAllRows}
                onDeselectAllRows={c.handleDeselectAllRows}
                bulkActions={c.bulkActions}
                onBulkAction={c.handleBulkActionClick}
                hasSearchableColumns={c.hasSearchableColumns}
                hasFilters={c.hasFilters}
                globalFilter={c.globalFilter}
                onGlobalFilterChange={c.setGlobalFilter}
                serverFilters={c.serverFilters}
                serverFilterState={c.serverFilterState}
                onSetSingleFilter={c.setSingleServerFilter}
                onToggleMultiFilterValue={c.toggleMultiServerFilterValue}
                onSetDateFilter={c.setDateServerFilter}
            />
            {c.tableAndFooter}
            {c.rowDetailDrawer &&
            c.detailDrawerOpen &&
            c.detailDrawerRow != null &&
            c.detailDrawerTitleColumn != null &&
            c.detailDrawerRowId != null ? (
                <DataTableRowDrawerPanel
                    open={c.detailDrawerOpen}
                    onOpenChange={c.handleDetailDrawerOpenChange}
                    row={c.detailDrawerRow}
                    rowId={c.detailDrawerRowId}
                    schemaColumns={c.schemaColumns}
                    titleColumn={c.detailDrawerTitleColumn}
                    onRowReplace={c.handleRowReplace}
                    bodyVariant={c.detailDrawerBodyVariant}
                    renderAttachBody={c.renderRowDrawerAttachBody}
                    flatpackEntity={flatpackEntity}
                    flatpackTableFieldId={flatpackTableFieldId}
                    columnValidationErrorsById={
                        c.rowValidationFieldErrorsById[c.detailDrawerRowId] ??
                        {}
                    }
                />
            ) : null}
            <FlatpackConfirmDialog
                open={c.pendingEmbeddedRowConfirm !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        c.dismissPendingRowActionConfirm();
                    }
                }}
                title={c.pendingEmbeddedRowConfirm?.button?.label ?? 'Confirm'}
                continueVariant={
                    c.pendingEmbeddedRowConfirm?.button?.variant ===
                    'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    c.confirmPendingRowAction();
                }}
            />
        </div>
    );
}
