import type { ReactNode } from 'react';

export type FlatpackDataTableSelectOptionStatus =
    | 'success'
    | 'pending'
    | 'warning'
    | 'error'
    | 'info';

export type FlatpackDataTableColumnOption = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
    icon?: string;
};

export type FlatpackDataTableColumnMeta = {
    label: string;
};

export type FlatpackDataTableActionButton = {
    label: string;
    icon?: string;
    action?: string;
    href?: string;
    url?: string;
};

export type FlatpackActionVariant =
    | 'default'
    | 'outline'
    | 'secondary'
    | 'ghost'
    | 'destructive'
    | 'link';

export type FlatpackDataTableBulkAction = {
    id: string;
    label: string;
    action?: string;
    icon?: string;
    variant?: FlatpackActionVariant;
};

export type FlatpackDataTableColumn = {
    id: string;
    label: string;
    type?:
        | 'text'
        | 'select'
        | 'date'
        | 'actions'
        | 'badge'
        | 'status'
        | 'relation';
    relation?: string;
    relationName?: string;
    relationValue?: string;
    options?: FlatpackDataTableColumnOption[];
    buttons?: Record<string, FlatpackDataTableActionButton>;
    format?: string;
    timezone?: string;
    sortable?: boolean;
    searchable?: boolean;
    editable?: boolean;
    detailDrawer?: boolean;
    invisible?: boolean;
    truncate?: number;
};

export type FlatpackDataTableFilterDateMode = 'exact' | 'from';

export type FlatpackDataTableFilter = {
    id: string;
    label: string;
    placeholder?: string;
    type: 'select' | 'date';
    multiple?: boolean;
    mode?: FlatpackDataTableFilterDateMode;
    options?: FlatpackDataTableColumnOption[];
};

export type FlatpackDataTableServerFilterValue = string | string[] | null;

export type FlatpackDataTableServerFiltersState = Record<
    string,
    FlatpackDataTableServerFilterValue
>;

export type BuildDataTableColumnDefsOptions = {
    hasBulkActions?: boolean;
    reorderable?: boolean;
    onCellChange?: (rowId: string, columnId: string, value: unknown) => void;
    onRowReplace?: (rowId: string, nextRow: Record<string, unknown>) => void;
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
