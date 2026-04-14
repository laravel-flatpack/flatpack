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

export type FlatpackDataTableColumn = {
    id: string;
    label: string;
    type?: 'text' | 'select' | 'date' | 'actions' | 'badge' | 'relation';
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

export type BuildDataTableColumnDefsOptions = {
    checkboxes?: boolean;
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

export type DataTableProps = {
    id: string;
    columns: FlatpackDataTableColumn[];
    data: Record<string, unknown>[];
    checkboxes?: boolean;
    reorderable?: boolean | string;
    onValueChange?: (value: unknown) => void;
    className?: string;
    toolbarStart?: ReactNode;
    toolbarAfterColumns?: ReactNode;
    serverPagination?: FlatpackListServerPagination;
    onServerPaginationChange?: (page: number, perPage: number) => void;
};
