import type { ReactNode } from 'react';

/** Allowed values for YAML {@code success_redirect} on Flatpack actions (server-driven redirects). */
export type FlatpackSuccessRedirect =
    typeof import('@/lib/generated/composition-schema-keys').SUCCESS_REDIRECT_VALUES[number];

export type FlatpackDataTableSelectOptionStatus =
    typeof import('@/lib/generated/composition-schema-keys').OPTION_STATUS_VALUES[number];

export type FlatpackDataTableColumnOption = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
    icon?: string;
};

export type FlatpackDataTableColumnMeta = {
    label: string;
};

export type FlatpackActionVariant =
    typeof import('@/lib/generated/composition-schema-keys').BUTTON_VARIANT_UI_VALUES[number];

export type FlatpackDataTableActionButton = {
    label: string;
    icon?: string;
    action?: string;
    href?: string;
    variant?: FlatpackActionVariant;
    success_redirect?: FlatpackSuccessRedirect;
    /** When true, list row actions show a confirmation dialog before POST. */
    confirm?: boolean;
    success_message?: string;
};

export type FlatpackDataTableBulkAction = {
    id: string;
    label: string;
    action?: string;
    icon?: string;
    variant?: FlatpackActionVariant;
    success_message?: string;
    confirm?: boolean;
    success_redirect?: FlatpackSuccessRedirect;
};

/** Normalized column display type ({@link LIST_COLUMN_YAML_TYPES} with {@code datetime} folded into {@code date}). */
export type FlatpackDataTableColumnType = Exclude<
    typeof import('@/lib/generated/composition-schema-keys').LIST_COLUMN_YAML_TYPES[number],
    'datetime'
>;

export type FlatpackDataTableColumn = {
    id: string;
    label: string;
    type?: FlatpackDataTableColumnType;
    relation?: string;
    relationName?: string;
    relationValue?: string;
    options?: FlatpackDataTableColumnOption[];
    actions?: FlatpackDataTableActionButton[];
    format?: string;
    timezone?: string;
    sortable?: boolean;
    searchable?: boolean;
    editable?: boolean;
    detailDrawer?: boolean;
    invisible?: boolean;
    truncate?: number;
};

export type FlatpackDataTableFilterDateMode =
    typeof import('@/lib/generated/composition-schema-keys').LIST_FILTER_DATE_MODES[number];

export type FlatpackDataTableFilterType =
    typeof import('@/lib/generated/composition-schema-keys').LIST_FILTER_TYPES[number];

export type FlatpackDataTableFilter = {
    id: string;
    label: string;
    placeholder?: string;
    type: FlatpackDataTableFilterType;
    multiple?: boolean;
    mode?: FlatpackDataTableFilterDateMode;
    options?: FlatpackDataTableColumnOption[];
};

export type FlatpackDataTableServerFilterValue = string | string[] | null;

export type FlatpackDataTableServerFiltersState = Record<
    string,
    FlatpackDataTableServerFilterValue
>;

export type DataTableRowActionPayload = {
    action: string;
    row: Record<string, unknown>;
    /** Present for `type: actions` column buttons (schema-driven confirm / toast). */
    button?: FlatpackDataTableActionButton;
};

export type BuildDataTableColumnDefsOptions = {
    hasBulkActions?: boolean;
    reorderable?: boolean;
    onCellChange?: (rowId: string, columnId: string, value: unknown) => void;
    onRowReplace?: (rowId: string, nextRow: Record<string, unknown>) => void;
    onRowAction?: (payload: DataTableRowActionPayload) => void | Promise<void>;
};

export type DataTableCellUpdatePayload = {
    rowId: string;
    row: Record<string, unknown>;
    columnId: string;
    value: unknown;
};

export type DataTableRowUpdatePayload = {
    rowId: string;
    row: Record<string, unknown>;
};

export type FlatpackListServerPagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type FlatpackListServerSorting = {
    sort_by: string | null;
    sort_direction: 'asc' | 'desc' | null;
};

export type DataTableBulkDeletePayload = {
    action: string;
    selection: 'all' | string[];
    search: string;
    filters: FlatpackDataTableServerFiltersState;
    sorting: FlatpackListServerSorting;
};

export type DataTableProps = {
    id: string;
    columns: FlatpackDataTableColumn[];
    data: Record<string, unknown>[];
    dataRowKey?: string;
    bulkActions?: FlatpackDataTableBulkAction[];
    reorderable?: boolean | string;
    onRowClick?: (row: Record<string, unknown>) => void;
    onValueChange?: (value: unknown) => void;
    onBulkAction?: (
        payload: DataTableBulkDeletePayload,
    ) => void | Promise<void>;
    onRowAction?: (payload: DataTableRowActionPayload) => void | Promise<void>;
    onCellUpdate?: (
        payload: DataTableCellUpdatePayload,
    ) => void | Promise<void>;
    onRowUpdate?: (payload: DataTableRowUpdatePayload) => void | Promise<void>;
    className?: string;
    toolbarStart?: ReactNode;
    toolbarAfterColumns?: ReactNode;
    serverPagination?: FlatpackListServerPagination;
    serverSearch?: string;
    serverFilters?: FlatpackDataTableFilter[];
    serverFilterValues?: FlatpackDataTableServerFiltersState;
    serverSorting?: FlatpackListServerSorting;
    onServerPaginationChange?: (
        page: number,
        perPage: number,
        search?: string,
        filters?: FlatpackDataTableServerFiltersState,
        sorting?: FlatpackListServerSorting,
    ) => void;
};
