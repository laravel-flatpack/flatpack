'use client';

import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/actions/confirm-dialog';
import { DataTable } from '@/components/table/data-table';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import { buildDashboardWidgetTableVisitSearch } from '@/lib/dashboard-widget-table-url';
import { listYamlColumnsToDataTableColumns } from '@/lib/list-schema';
import {
    bulkDashboardWidgetModelRows,
    runDashboardWidgetModelRowAction,
    updateDashboardWidgetModelRow,
} from '@/lib/model-table-row-update';
import type {
    DataTableBulkDeletePayload,
    DataTableRowActionPayload,
    DataTableRowUpdatePayload,
    FlatpackDataTableBulkAction,
    FlatpackListServerPagination,
} from '@/types/data-table';
import type { FlatpackTableWidget } from '@/types/widgets-composition';

const EMPTY_TABLE_ROWS: Record<string, unknown>[] = [];
const EMPTY_BULK_ACTIONS: FlatpackDataTableBulkAction[] = [];

type TableWidgetProps = {
    widgetId: string;
    widget: FlatpackTableWidget;
};

export function TableWidget({ widgetId, widget }: TableWidgetProps) {
    const [pendingRowActionConfirm, setPendingRowActionConfirm] =
        useState<DataTableRowActionPayload | null>(null);
    const providerBacked =
        typeof widget.provider === 'string' && widget.provider.trim() !== '';
    const modelBacked =
        typeof widget.model === 'string' && widget.model.trim() !== '';
    const columns = useMemo(
        () => listYamlColumnsToDataTableColumns(widget.columns),
        [widget.columns],
    );
    const rows = useMemo(() => {
        if (Array.isArray(widget.data?.rows)) {
            return widget.data.rows;
        }
        return EMPTY_TABLE_ROWS;
    }, [widget.data?.rows]);
    const bulkActions = useMemo(
        () =>
            Array.isArray(widget.bulk_actions)
                ? widget.bulk_actions
                : EMPTY_BULK_ACTIONS,
        [widget.bulk_actions],
    );
    const resolvedDefaultSort = useMemo(() => {
        const sorting = widget.data?.sorting;
        if (
            typeof sorting?.sort_by === 'string' &&
            sorting.sort_by.trim() !== '' &&
            (sorting.sort_direction === 'asc' ||
                sorting.sort_direction === 'desc')
        ) {
            return {
                key: sorting.sort_by,
                direction: sorting.sort_direction,
            };
        }
        return widget.default_sort;
    }, [widget.data?.sorting, widget.default_sort]);
    const pagination =
        typeof widget.pagination === 'boolean' ? widget.pagination : undefined;
    const serverPagination = useMemo(():
        | FlatpackListServerPagination
        | undefined => {
        if (!modelBacked) {
            return undefined;
        }
        const p = widget.data?.pagination;
        if (p == null) {
            return undefined;
        }
        return {
            current_page: p.current_page,
            last_page: p.last_page,
            per_page: p.per_page,
            total: p.total,
            from: p.from,
            to: p.to,
        };
    }, [modelBacked, widget.data?.pagination]);
    const serverSorting = useMemo(() => {
        if (!(modelBacked && serverPagination != null)) {
            return undefined;
        }
        return {
            sort_by: widget.data?.sorting?.sort_by ?? null,
            sort_direction: widget.data?.sorting?.sort_direction ?? null,
        };
    }, [modelBacked, serverPagination, widget.data?.sorting]);
    const onServerPaginationChange = useCallback(
        (
            page: number,
            perPage: number,
            search?: string,
            _filters?: unknown,
            sorting?: {
                sort_by: string | null;
                sort_direction: 'asc' | 'desc' | null;
            },
        ) => {
            const nextSearch = buildDashboardWidgetTableVisitSearch(
                window.location.search,
                widgetId,
                {
                    page,
                    perPage,
                    search: search ?? '',
                    sorting: {
                        sort_by: sorting?.sort_by ?? null,
                        sort_direction: sorting?.sort_direction ?? null,
                    },
                },
            );
            const path = window.location.pathname;
            router.visit(`${path}${nextSearch}`, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [widgetId],
    );
    const onProviderRowClick = useCallback(
        (row: Record<string, unknown>) => {
            if (!providerBacked) {
                return;
            }
            const hrefRaw = row.href;
            if (typeof hrefRaw !== 'string') {
                return;
            }
            const href = hrefRaw.trim();
            if (href === '') {
                return;
            }
            if (/^https?:\/\//i.test(href)) {
                window.location.assign(href);
                return;
            }
            router.visit(href);
        },
        [providerBacked],
    );
    const onRowUpdate = useCallback(
        async ({ rowId, row }: DataTableRowUpdatePayload) => {
            const recordId = String(rowId ?? '').trim();
            if (recordId === '') {
                throw new Error('Row id is required');
            }
            return await updateDashboardWidgetModelRow({
                widgetId,
                rowId: recordId,
                values: row,
            });
        },
        [widgetId],
    );
    const onBulkAction = useCallback(
        async (payload: DataTableBulkDeletePayload) => {
            await bulkDashboardWidgetModelRows({
                widgetId,
                action: payload.action,
                selection: payload.selection,
                search: payload.search,
                filters: payload.filters,
                sorting: payload.sorting,
            });
            const cfg = bulkActions.find(
                (item) => item.action === payload.action,
            );
            if (cfg?.success_message) {
                toast.success(cfg.success_message);
            }
        },
        [widgetId, bulkActions],
    );
    const executeRowAction = useCallback(
        async (payload: DataTableRowActionPayload) => {
            const recordId = String(payload.row.id ?? '').trim();
            if (recordId === '') {
                throw new Error('Row id is required');
            }
            await runDashboardWidgetModelRowAction({
                widgetId,
                rowId: recordId,
                action: payload.action,
                successMessage: payload.button?.success_message,
            });
        },
        [widgetId],
    );
    const onRowAction = useCallback(
        async (payload: DataTableRowActionPayload) => {
            if (payload.button?.confirm === true) {
                setPendingRowActionConfirm(payload);
                return;
            }
            await executeRowAction(payload);
        },
        [executeRowAction],
    );
    const tableId = `widget-table-${widgetId.toLowerCase().replaceAll(/\s+/g, '-')}`;
    const labelId = `${tableId}-label`;
    const labelText =
        typeof widget.label === 'string' ? widget.label.trim() : '';
    const descriptionText =
        typeof widget.description === 'string' ? widget.description.trim() : '';
    const showFieldChrome = labelText !== '' || descriptionText !== '';

    const dataTable = (
        <DataTable
            id={tableId}
            regionLabelledBy={labelText !== '' ? labelId : undefined}
            columns={columns}
            data={rows}
            inlineCellEdit={false}
            bulkActions={modelBacked ? bulkActions : EMPTY_BULK_ACTIONS}
            pagination={pagination}
            showColumnsVisibility={widget.showColumnsVisibility ?? false}
            serverPagination={serverPagination}
            serverSearch={
                modelBacked && widget.data?.search != null
                    ? String(widget.data.search)
                    : undefined
            }
            serverSorting={serverSorting}
            onServerPaginationChange={
                modelBacked && serverPagination != null
                    ? onServerPaginationChange
                    : undefined
            }
            defaultSort={resolvedDefaultSort}
            requireRowIdForActions={providerBacked}
            rowDetailDrawer={modelBacked}
            openDetailDrawerOnRowClick={modelBacked}
            onRowClick={providerBacked ? onProviderRowClick : undefined}
            flatpackWidgetId={widgetId}
            onRowUpdate={modelBacked ? onRowUpdate : undefined}
            onRowAction={modelBacked ? onRowAction : undefined}
            onBulkAction={
                modelBacked && bulkActions.length > 0 ? onBulkAction : undefined
            }
        />
    );

    if (!showFieldChrome) {
        return dataTable;
    }

    const showTitle = labelText !== '' || descriptionText !== '';

    return (
        <Field>
            {showTitle && (
                <div className="mt-6 flex flex-col gap-2">
                    {labelText !== '' ? (
                        <FieldTitle id={labelId} className="text-sm font-bold">
                            {labelText}
                        </FieldTitle>
                    ) : null}
                    {descriptionText !== '' ? (
                        <FieldDescription className="text-sm text-muted-foreground">
                            {descriptionText}
                        </FieldDescription>
                    ) : null}
                </div>
            )}
            <FieldContent>{dataTable}</FieldContent>
            <ConfirmDialog
                open={pendingRowActionConfirm !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingRowActionConfirm(null);
                    }
                }}
                title={pendingRowActionConfirm?.button?.label ?? 'Confirm'}
                continueVariant={
                    pendingRowActionConfirm?.button?.variant === 'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    const payload = pendingRowActionConfirm;
                    setPendingRowActionConfirm(null);
                    if (payload != null) {
                        void executeRowAction(payload);
                    }
                }}
            />
        </Field>
    );
}
