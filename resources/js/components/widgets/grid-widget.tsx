'use client';

import { router } from '@inertiajs/react';
import type { Row } from '@tanstack/react-table';
import { type MouseEvent, useCallback, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/actions/confirm-dialog';
import { RowActionButton } from '@/components/actions/row-action-button';
import { ColumnCellDispatcher } from '@/components/list-columns/column-cell-dispatcher';
import {
    DATA_TABLE_EMPTY_RESULTS_LABEL,
    DATA_TABLE_LABEL,
    DATA_TABLE_ROW_CLICK_IGNORE_SELECTOR,
    GRID_WIDGET_PAGE_SIZE_OPTIONS,
} from '@/components/table/data-table-constants';
import { DataTableFooter } from '@/components/table/data-table-footer';
import { DataTableRowDrawerPanel } from '@/components/table/data-table-row-drawer';
import { DataTableToolbar } from '@/components/table/data-table-toolbar';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import { useDataTableController } from '@/hooks/use-data-table-controller';
import { buildDashboardWidgetTableVisitSearch } from '@/lib/dashboard-widget-table-url';
import {
    isEmbeddedTableBelongsToManyAttachToolbarAction,
    isEmbeddedTableCreateDraftToolbarAction,
} from '@/lib/data-table-action-semantics';
import { stableRowActionButtonKey } from '@/lib/data-table-row-action';
import {
    formatCellValue,
    formatRelationCellDisplay,
} from '@/lib/data-table-utils';
import { normalizeFormTableToolbarActionsInput } from '@/lib/form-table-toolbar-actions';
import { inertiaPostMutation } from '@/lib/inertia-mutation';
import { listYamlColumnsToDataTableColumns } from '@/lib/list-schema';
import {
    bulkDashboardWidgetModelRows,
    runDashboardWidgetModelRowAction,
    updateDashboardWidgetModelRow,
} from '@/lib/model-table-row-update';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';
import type {
    DataTableBulkDeletePayload,
    DataTableRowActionPayload,
    DataTableRowUpdatePayload,
    FlatpackDataTableActionButton,
    FlatpackDataTableBulkAction,
    FlatpackDataTableColumn,
    FlatpackListServerPagination,
} from '@/types/data-table';
import type {
    FlatpackGridCardSlotMap,
    FlatpackGridWidget,
} from '@/types/widgets-composition';

const EMPTY_ROWS: Record<string, unknown>[] = [];
const EMPTY_BULK_ACTIONS: FlatpackDataTableBulkAction[] = [];

type GridWidgetProps = {
    widgetId: string;
    widget: FlatpackGridWidget;
};

type ResolvedCardSlots = {
    title: FlatpackDataTableColumn | null;
    subtitle: FlatpackDataTableColumn | null;
    image: FlatpackDataTableColumn | null;
    badges: FlatpackDataTableColumn[];
    body: FlatpackDataTableColumn[];
    footerActions: FlatpackDataTableColumn | null;
};

function findColumnById(
    columns: FlatpackDataTableColumn[],
    id: string | undefined,
): FlatpackDataTableColumn | null {
    if (id == null || id === '') {
        return null;
    }
    return columns.find((column) => column.id === id) ?? null;
}

/**
 * Resolve which column drives each card region. When the YAML provides an explicit `card:` slot
 * map, those wins (unknown column ids are silently ignored). Otherwise we auto-derive: first
 * text-like column → title; badge/status columns → badges; an `actions` column → footer; the
 * remaining non-action columns → body label/value pairs.
 */
export function resolveGridWidgetCardSlots(
    columns: FlatpackDataTableColumn[],
    explicit: FlatpackGridCardSlotMap | undefined,
): ResolvedCardSlots {
    const usedIds = new Set<string>();
    const claim = (column: FlatpackDataTableColumn | null) => {
        if (column != null) {
            usedIds.add(column.id);
        }
        return column;
    };

    if (explicit != null) {
        const title = claim(findColumnById(columns, explicit.title));
        const subtitle = claim(findColumnById(columns, explicit.subtitle));
        const image = claim(findColumnById(columns, explicit.image));
        const footerActions = claim(
            findColumnById(columns, explicit.footer_actions),
        );
        const badges = (explicit.badges ?? [])
            .map((id) => claim(findColumnById(columns, id)))
            .filter((c): c is FlatpackDataTableColumn => c != null);
        const body = (explicit.body ?? [])
            .map((id) => claim(findColumnById(columns, id)))
            .filter((c): c is FlatpackDataTableColumn => c != null);
        return { title, subtitle, image, badges, body, footerActions };
    }

    const textLike = columns.filter(
        (column) =>
            column.type === undefined ||
            column.type === 'text' ||
            column.type === 'relation' ||
            column.type === 'date',
    );
    const title = claim(textLike[0] ?? columns[0] ?? null);
    const badgeColumns = columns.filter(
        (column) =>
            !usedIds.has(column.id) &&
            (column.type === 'badge' || column.type === 'status'),
    );
    badgeColumns.forEach(claim);
    const footerActions = claim(
        columns.find(
            (column) => !usedIds.has(column.id) && column.type === 'actions',
        ) ?? null,
    );
    const body = columns.filter(
        (column) => !usedIds.has(column.id) && column.type !== 'actions',
    );
    return {
        title,
        subtitle: null,
        image: null,
        badges: badgeColumns,
        body,
        footerActions,
    };
}

function rowValue(row: Record<string, unknown>, columnId: string): unknown {
    return row[columnId];
}

/** Plain label for row-selection `aria-label` (relation-aware). */
function gridCardLabelPlainText(
    row: Record<string, unknown>,
    column: FlatpackDataTableColumn | null,
): string {
    if (column == null) {
        return '';
    }
    if (column.type === 'relation' && column.relation && column.relationName) {
        const fromRel = formatRelationCellDisplay(row, column).trim();
        if (fromRel !== '') {
            return fromRel;
        }
    }
    return formatCellValue(row[column.id]).trim();
}

/**
 * When the image slot column holds a URL-like string (non-relation), render the hero image;
 * otherwise the slot uses the same cell stack as the table.
 */
function imageColumnUrlForCard(
    column: FlatpackDataTableColumn | null,
    raw: unknown,
): string | null {
    if (column == null || column.type === 'relation') {
        return null;
    }
    if (typeof raw !== 'string') {
        return null;
    }
    const trimmed = raw.trim();
    if (trimmed === '') {
        return null;
    }
    if (
        /^https?:\/\//i.test(trimmed) ||
        trimmed.startsWith('/') ||
        trimmed.startsWith('data:')
    ) {
        return trimmed;
    }
    return null;
}

function GridColumnCellReadOnly({
    column,
    row,
    rowId,
    schemaColumns,
}: {
    column: FlatpackDataTableColumn;
    row: Record<string, unknown>;
    rowId: string;
    schemaColumns: FlatpackDataTableColumn[];
}) {
    if (column.type === 'actions') {
        return <span className="text-muted-foreground">—</span>;
    }
    return (
        <ColumnCellDispatcher
            column={column}
            row={row}
            rowId={rowId}
            schemaColumns={schemaColumns}
            value={row[column.id]}
            inlineCellEdit={false}
        />
    );
}

export function GridWidget({ widgetId, widget }: GridWidgetProps) {
    const [pendingRowActionConfirm, setPendingRowActionConfirm] =
        useState<DataTableRowActionPayload | null>(null);

    const providerBacked =
        typeof widget.provider === 'string' && widget.provider.trim() !== '';
    const modelBacked =
        typeof widget.model === 'string' && widget.model.trim() !== '';

    const columns = useMemo(
        () => listYamlColumnsToDataTableColumns(widget.columns ?? {}),
        [widget.columns],
    );
    const cardSlots = useMemo(
        () => resolveGridWidgetCardSlots(columns, widget.card),
        [columns, widget.card],
    );

    const rows = useMemo(() => {
        if (Array.isArray(widget.data?.rows)) {
            return widget.data.rows;
        }
        return EMPTY_ROWS;
    }, [widget.data?.rows]);

    const bulkActions = useMemo(
        () =>
            Array.isArray(widget.bulk_actions)
                ? widget.bulk_actions
                : EMPTY_BULK_ACTIONS,
        [widget.bulk_actions],
    );

    const toolbarActions = useMemo(
        () =>
            normalizeFormTableToolbarActionsInput(widget.actions, undefined) ??
            [],
        [widget.actions],
    );

    const listEntity = useMemo(() => {
        const raw = widget.list_entity;
        if (typeof raw !== 'string') {
            return undefined;
        }
        const t = raw.trim();
        return t !== '' ? t : undefined;
    }, [widget.list_entity]);

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

    const onToolbarAction = useCallback(
        async (actionId: string) => {
            const def = toolbarActions.find((b) => b.id === actionId);
            if (def == null) {
                return;
            }
            if (
                isEmbeddedTableCreateDraftToolbarAction(def.action) ||
                isEmbeddedTableBelongsToManyAttachToolbarAction(def.action)
            ) {
                return;
            }
            if (listEntity == null) {
                toast.error(
                    'Set `entity` or `list_entity` on this grid widget for custom toolbar actions.',
                );
                return;
            }
            try {
                await inertiaPostMutation(
                    route('flatpack.entities.action', { entity: listEntity }),
                    { action: def.action },
                    {
                        errorMessage: 'Action failed',
                    },
                );
            } catch (error) {
                const message =
                    error instanceof Error ? error.message : 'Action failed';
                toast.error(message);
            }
        },
        [listEntity, toolbarActions],
    );

    const tableId = `widget-grid-${widgetId.toLowerCase().replaceAll(/\s+/g, '-')}`;

    const c = useDataTableController({
        id: tableId,
        columns,
        data: rows,
        inlineCellEdit: false,
        bulkActions: modelBacked ? bulkActions : EMPTY_BULK_ACTIONS,
        pagination,
        showColumnsVisibility: widget.showColumnsVisibility ?? false,
        serverPagination,
        serverSearch:
            modelBacked && widget.data?.search != null
                ? String(widget.data.search)
                : undefined,
        serverSorting,
        onServerPaginationChange:
            modelBacked && serverPagination != null
                ? onServerPaginationChange
                : undefined,
        defaultSort: resolvedDefaultSort,
        requireRowIdForActions: providerBacked,
        rowDetailDrawer: modelBacked,
        openDetailDrawerOnRowClick: modelBacked,
        onRowClick: providerBacked ? onProviderRowClick : undefined,
        flatpackWidgetId: widgetId,
        onRowUpdate: modelBacked ? onRowUpdate : undefined,
        onRowAction: modelBacked ? onRowAction : undefined,
        onBulkAction:
            modelBacked && bulkActions.length > 0 ? onBulkAction : undefined,
        ...(toolbarActions.length > 0
            ? {
                  toolbarActions,
                  onToolbarAction,
              }
            : {}),
    });

    const hasToolbarContent =
        c.hasToolbarActions ||
        c.hasBulkActions ||
        c.hasSearchableColumns ||
        c.hasFilters ||
        c.showColumnsVisibility;

    const paginationStateCurrent = c.table.getState().pagination;
    const pageCount = c.table.getPageCount() || 1;
    const shouldShowPagination =
        c.pagination === true
            ? true
            : c.pagination === false
              ? false
              : pageCount > 1;

    const filteredRows = c.table.getFilteredRowModel().rows;
    const labelId = `${tableId}-label`;
    const labelText =
        typeof widget.label === 'string' ? widget.label.trim() : '';
    const descriptionText =
        typeof widget.description === 'string' ? widget.description.trim() : '';
    const showFieldChrome = labelText !== '' || descriptionText !== '';

    const ariaLabelledBy = labelText !== '' ? labelId : c.tableLabelId;

    const gridBody =
        filteredRows.length === 0 ? (
            <p className="rounded-md border border-dashed bg-muted/20 px-4 py-8 text-center text-sm text-muted-foreground">
                {DATA_TABLE_EMPTY_RESULTS_LABEL}
            </p>
        ) : (
            <div className="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                {filteredRows.map((tableRow) => (
                    <GridWidgetCard
                        key={tableRow.id}
                        tableRow={tableRow}
                        slots={cardSlots}
                        schemaColumns={columns}
                        modelBacked={modelBacked}
                        providerBacked={providerBacked}
                        rowDetailDrawer={c.rowDetailDrawer}
                        onOpenDetailDrawerForRow={c.openDetailDrawerForRow}
                        onProviderRowClick={onProviderRowClick}
                        onRowAction={c.handleRowAction}
                        hasBulkActions={c.hasBulkActions}
                    />
                ))}
            </div>
        );

    const region = (
        <div
            className={cn(
                'flex w-full flex-col',
                hasToolbarContent ? 'gap-4' : 'gap-0',
            )}
            role="region"
            aria-labelledby={ariaLabelledBy}
        >
            {labelText === '' ? (
                <span id={c.tableLabelId} className="sr-only">
                    {DATA_TABLE_LABEL}
                </span>
            ) : null}
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
                showColumnsVisibility={c.showColumnsVisibility}
                globalFilter={c.globalFilter}
                onGlobalFilterChange={c.setGlobalFilter}
                serverFilters={c.serverFilters}
                serverFilterState={c.serverFilterState}
                onSetSingleFilter={c.setSingleServerFilter}
                onToggleMultiFilterValue={c.toggleMultiServerFilterValue}
                onSetDateFilter={c.setDateServerFilter}
            />
            {gridBody}
            {shouldShowPagination ? (
                <DataTableFooter
                    id={c.id}
                    rowCountLabel={c.rowCountLabel}
                    pageSizeOptions={GRID_WIDGET_PAGE_SIZE_OPTIONS}
                    pageSize={paginationStateCurrent.pageSize}
                    pageIndex={paginationStateCurrent.pageIndex}
                    pageCount={pageCount}
                    canPreviousPage={c.table.getCanPreviousPage()}
                    canNextPage={c.table.getCanNextPage()}
                    onPageSizeChange={(value) => {
                        c.table.setPageSize(Number(value));
                    }}
                    onFirstPage={() => c.table.setPageIndex(0)}
                    onPreviousPage={() => c.table.previousPage()}
                    onNextPage={() => c.table.nextPage()}
                    onLastPage={() => c.table.setPageIndex(pageCount - 1)}
                />
            ) : null}
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
                    flatpackWidgetId={widgetId}
                    columnValidationErrorsById={
                        c.rowValidationFieldErrorsById[c.detailDrawerRowId] ??
                        {}
                    }
                />
            ) : null}
            <ConfirmDialog
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

    const chrome = (
        <>
            {region}
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
        </>
    );

    if (!showFieldChrome) {
        return chrome;
    }

    return (
        <Field>
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
            <FieldContent>{chrome}</FieldContent>
        </Field>
    );
}

type GridWidgetCardProps = {
    tableRow: Row<Record<string, unknown>>;
    slots: ResolvedCardSlots;
    schemaColumns: FlatpackDataTableColumn[];
    modelBacked: boolean;
    providerBacked: boolean;
    rowDetailDrawer: boolean;
    onOpenDetailDrawerForRow: (rowId: string) => void;
    onProviderRowClick: (row: Record<string, unknown>) => void;
    onRowAction: (payload: DataTableRowActionPayload) => void;
    hasBulkActions: boolean;
};

function GridWidgetCard({
    tableRow,
    slots,
    schemaColumns,
    modelBacked,
    providerBacked,
    rowDetailDrawer,
    onOpenDetailDrawerForRow,
    onProviderRowClick,
    onRowAction,
    hasBulkActions,
}: GridWidgetCardProps) {
    const row = tableRow.original;
    const rowId = tableRow.id;

    const titlePlain = gridCardLabelPlainText(row, slots.title);
    const imageRaw =
        slots.image != null ? rowValue(row, slots.image.id) : undefined;
    const imageUrl =
        slots.image != null
            ? imageColumnUrlForCard(slots.image, imageRaw)
            : null;

    const footerButtons = useMemo(() => {
        if (slots.footerActions == null) {
            return [] as FlatpackDataTableActionButton[];
        }
        const list = slots.footerActions.actions;
        return Array.isArray(list) ? list : [];
    }, [slots.footerActions]);

    const cardShellClickable =
        (modelBacked && rowDetailDrawer) || providerBacked;

    const handleCardClick = (event: MouseEvent<HTMLDivElement>) => {
        if (!cardShellClickable) {
            return;
        }
        const target = event.target;
        if (
            target instanceof Element &&
            target.closest(DATA_TABLE_ROW_CLICK_IGNORE_SELECTOR)
        ) {
            return;
        }
        if (modelBacked && rowDetailDrawer) {
            onOpenDetailDrawerForRow(rowId);
            return;
        }
        if (providerBacked) {
            onProviderRowClick(row);
        }
    };

    return (
        <Card
            data-testid="widget-grid-card"
            data-row-id={rowId}
            className={cn(cardShellClickable && 'cursor-pointer')}
            onClick={handleCardClick}
        >
            {slots.image != null && imageUrl != null ? (
                <img
                    src={imageUrl}
                    alt=""
                    className="aspect-video w-full object-cover"
                />
            ) : slots.image != null ? (
                <div className="border-b px-6 pt-2 pb-4 text-sm">
                    <GridColumnCellReadOnly
                        column={slots.image}
                        row={row}
                        rowId={rowId}
                        schemaColumns={schemaColumns}
                    />
                </div>
            ) : null}
            <CardHeader className="flex flex-row items-start justify-between gap-2">
                <div className="flex min-w-0 flex-col gap-1">
                    {slots.title != null ? (
                        <CardTitle className="min-w-0">
                            <div className="truncate">
                                <GridColumnCellReadOnly
                                    column={slots.title}
                                    row={row}
                                    rowId={rowId}
                                    schemaColumns={schemaColumns}
                                />
                            </div>
                        </CardTitle>
                    ) : null}
                    {slots.subtitle != null ? (
                        <div className="min-w-0 truncate text-sm text-muted-foreground">
                            <GridColumnCellReadOnly
                                column={slots.subtitle}
                                row={row}
                                rowId={rowId}
                                schemaColumns={schemaColumns}
                            />
                        </div>
                    ) : null}
                    {slots.badges.length > 0 && (
                        <div className="mt-1 flex flex-wrap gap-1">
                            {slots.badges.map((column) => (
                                <div
                                    key={column.id}
                                    className="max-w-full min-w-0 shrink-0"
                                >
                                    <GridColumnCellReadOnly
                                        column={column}
                                        row={row}
                                        rowId={rowId}
                                        schemaColumns={schemaColumns}
                                    />
                                </div>
                            ))}
                        </div>
                    )}
                </div>
                {hasBulkActions && (
                    <Checkbox
                        data-testid={`grid-row-select-${rowId}`}
                        checked={tableRow.getIsSelected()}
                        onClick={(event) => event.stopPropagation()}
                        onCheckedChange={(value) => {
                            tableRow.toggleSelected(!!value);
                        }}
                        aria-label={`Select ${titlePlain || rowId}`}
                    />
                )}
            </CardHeader>
            {slots.body.length > 0 && (
                <CardContent>
                    <dl className="grid grid-cols-[max-content_1fr] gap-x-3 gap-y-1 text-sm">
                        {slots.body.map((column) => (
                            <div
                                key={column.id}
                                className="contents"
                                data-grid-card-field={column.id}
                            >
                                <dt className="text-muted-foreground">
                                    {column.label}
                                </dt>
                                <dd className="min-w-0">
                                    <GridColumnCellReadOnly
                                        column={column}
                                        row={row}
                                        rowId={rowId}
                                        schemaColumns={schemaColumns}
                                    />
                                </dd>
                            </div>
                        ))}
                    </dl>
                </CardContent>
            )}
            {footerButtons.length > 0 && (
                <CardFooter className="flex flex-wrap gap-2" data-no-row-click>
                    {footerButtons.map((button) => (
                        <RowActionButton
                            key={stableRowActionButtonKey(button)}
                            button={button}
                            row={row}
                            size="sm"
                            onAction={onRowAction}
                        />
                    ))}
                </CardFooter>
            )}
        </Card>
    );
}
