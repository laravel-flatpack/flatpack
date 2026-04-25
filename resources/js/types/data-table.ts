import type { RowSelectionState } from '@tanstack/react-table';
import type { Dispatch, ReactNode, SetStateAction } from 'react';

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

/**
 * Toolbar buttons above embedded form {@code type: table} fields.
 * {@code action: "create"} or {@code action: "add"} opens the same blank draft in the row drawer
 * (new row UX without requiring host {@code onToolbarAction}).
 * {@code action: "attach"} opens the same draft with `attachExisting` body variant for optional
 * `DataTable` `renderRowDrawerAttachBody` (BelongsToMany attach-by-id).
 */
export type FlatpackFormTableToolbarAction = {
    id: string;
    label: string;
    action: string;
    variant?: FlatpackActionVariant;
    icon?: string;
};

/** Normalized column display type ({@link LIST_COLUMN_YAML_TYPES} with {@code datetime} folded into {@code date}). */
export type FlatpackDataTableColumnType = Exclude<
    typeof import('@/lib/generated/composition-schema-keys').LIST_COLUMN_YAML_TYPES[number],
    'datetime'
>;

/**
 * Optional full edit field override for embedded table row drawers (`edit_form_field` in YAML).
 * Uses form field type names; `table` is accepted in schema but intentionally omitted at drawer runtime.
 */
export type FlatpackDataTableEditFormFieldType =
    typeof import('@/lib/generated/composition-schema-keys').FORM_FIELD_TYPES_CANONICAL[number];

export type FlatpackDataTableEditFormField = {
    type: FlatpackDataTableEditFormFieldType;
    [key: string]: unknown;
};

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
    editFormField?: FlatpackDataTableEditFormField;
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

export type DataTableRow = Record<string, unknown>;

export type DataTableRowActionPayload<TRow extends DataTableRow = DataTableRow> = {
    action: string;
    row: TRow;
    /** Present for `type: actions` column buttons (schema-driven confirm / toast). */
    button?: FlatpackDataTableActionButton;
};

export type BuildDataTableColumnDefsOptions<
    TRow extends DataTableRow = DataTableRow,
> = {
    hasBulkActions?: boolean;
    reorderable?: boolean;
    onCellChange?: (rowId: string, columnId: string, value: unknown) => void;
    onRowReplace?: (rowId: string, nextRow: TRow) => void;
    onRowAction?: (payload: DataTableRowActionPayload<TRow>) => void | Promise<void>;
};

export type DataTableCellUpdatePayload<
    TRow extends DataTableRow = DataTableRow,
> = {
    rowId: string;
    row: TRow;
    columnId: string;
    value: unknown;
};

export type DataTableRowUpdatePayload<
    TRow extends DataTableRow = DataTableRow,
> = {
    rowId: string;
    row: TRow;
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

export type UseDataTableCreateRowFlowOptions = {
    rowDetailDrawer: boolean;
    toolbarActions: FlatpackFormTableToolbarAction[];
    schemaColumns: FlatpackDataTableColumn[];
    rowIdentity: {
        getStableRowId: (row: DataTableRow, index: number) => string;
    };
    mutations: {
        data: DataTableRow[];
        onToolbarAction?: (actionId: string) => void;
    };
};

/**
 * Row drawer main area: default column fields, or BelongsToMany attach slot when
 * `attachExisting` and `renderRowDrawerAttachBody` is set on `DataTable`.
 */
export type DataTableRowDrawerBodyVariant = 'rowFields' | 'attachExisting';

export type DataTableRowDrawerAttachBodyRenderContext = {
    rowId: string;
    /** Mutable draft for the row (sync to parent on Save via `onRowReplace`). */
    draft: DataTableRow;
    setDraft: Dispatch<SetStateAction<DataTableRow>>;
    schemaColumns: FlatpackDataTableColumn[];
    titleColumn: FlatpackDataTableColumn;
    bodyVariant: DataTableRowDrawerBodyVariant;
    onRequestClose: () => void;
};

export type UseDataTableCreateRowFlowResult = {
    newRowIdPrefix: '__new__';
    detailDrawerOpen: boolean;
    detailDrawerRowId: string | null;
    detailDrawerRow: DataTableRow | null;
    /** `attachExisting` when the toolbar `action` is the BTM `attach` keyword. */
    detailDrawerBodyVariant: DataTableRowDrawerBodyVariant;
    openDetailDrawerForRow: (rowId: string) => void;
    handleDetailDrawerOpenChange: (open: boolean) => void;
    clearCreateDraftRow: () => void;
    resetDetailDrawer: () => void;
    handleToolbarActionClick: (actionId: string) => void;
};

export type UseDataTableRelationshipFlowOptions = {
    rowIdentity: {
        dataRowKey: string;
        getStableRowId: (row: DataTableRow, index: number) => string;
    };
    mutations: {
        data: DataTableRow[];
        setData: Dispatch<SetStateAction<DataTableRow[]>>;
        onValueChange?: (value: unknown) => void;
    };
    onRowAction?: (payload: DataTableRowActionPayload) => void | Promise<void>;
    onBulkAction?: (
        payload: DataTableBulkDeletePayload,
    ) => void | Promise<void>;
    bulkActions: FlatpackDataTableBulkAction[];
    rowSelection: RowSelectionState;
    setRowSelection: Dispatch<SetStateAction<RowSelectionState>>;
    isAllRowsSelected: boolean;
    setIsAllRowsSelected: Dispatch<SetStateAction<boolean>>;
    globalFilter: string;
    serverFilterState: FlatpackDataTableServerFiltersState;
    serverSortingForBulkAction: FlatpackListServerSorting;
};

export type UseDataTableRelationshipFlowResult = {
    handleRowAction: (payload: DataTableRowActionPayload) => void;
    handleBulkAction: (
        actionId: string,
        handleDeselectAllRows: () => void,
    ) => Promise<void>;
    pendingEmbeddedRowConfirm: DataTableRowActionPayload | null;
    dismissPendingRowActionConfirm: () => void;
    confirmPendingRowAction: () => void;
};

export type UseDataTableRowReplaceFlowOptions = {
    rowIdentity: {
        getStableRowId: (row: DataTableRow, index: number) => string;
        newRowIdPrefix: string;
    };
    mutations: {
        setData: Dispatch<SetStateAction<DataTableRow[]>>;
        onValueChange?: (value: unknown) => void;
        onRowUpdate?: (
            payload: DataTableRowUpdatePayload,
        ) => void | Promise<void>;
    };
    clearCreateDraftRow: () => void;
};

export type UseDataTableRowReplaceFlowResult = {
    handleRowReplace: (rowId: string, nextRow: DataTableRow) => void;
};

export type UseDataTableCellUpdateFlowOptions = {
    rowIdentity: {
        getStableRowId: (row: DataTableRow, index: number) => string;
    };
    mutations: {
        setData: Dispatch<SetStateAction<DataTableRow[]>>;
        onValueChange?: (value: unknown) => void;
        onCellUpdate?: (
            payload: DataTableCellUpdatePayload,
        ) => void | Promise<void>;
    };
};

export type UseDataTableCellUpdateFlowResult = {
    handleCellChange: (rowId: string, columnId: string, next: unknown) => void;
};

/**
 * Eloquent relation class behind an embedded `type: table` `relation` (from PHP
 * `FormEmbeddedTableRelationTypeResolver`: instanceof + reflection on the form model).
 */
export type FlatpackTableRelationType =
    | 'belongs_to_many'
    | 'has_many'
    | 'morph_many'
    | 'morph_to_many'
    | 'has_one'
    | 'unknown';

export type DataTableProps<TRow extends DataTableRow = DataTableRow> = {
    id: string;
    columns: FlatpackDataTableColumn[];
    data: TRow[];
    dataRowKey?: string;
    bulkActions?: FlatpackDataTableBulkAction[];
    toolbarActions?: FlatpackFormTableToolbarAction[];
    /** When true, toolbar buttons are disabled (e.g. parent record not persisted). */
    toolbarActionsDisabled?: boolean;
    toolbarActionsDisabledTitle?: string;
    onToolbarAction?: (actionId: string) => void;
    reorderable?: boolean | string;
    /**
     * When true, the row detail drawer is available (toolbar `create` draft flow, and
     * optionally opening a row from a click — see {@link openDetailDrawerOnRowClick}).
     */
    rowDetailDrawer?: boolean;
    /**
     * When true (default), a row click opens the detail drawer if {@link rowDetailDrawer}
     * is true. Set false to keep inline editing but disable drawer-on-row-click
     * (e.g. `row_detail_drawer: false` on embedded `type: table` fields).
     */
    openDetailDrawerOnRowClick?: boolean;
    onRowClick?: (row: TRow) => void;
    onValueChange?: (value: unknown) => void;
    onBulkAction?: (
        payload: DataTableBulkDeletePayload,
    ) => void | Promise<void>;
    onRowAction?: (payload: DataTableRowActionPayload<TRow>) => void | Promise<void>;
    onCellUpdate?: (
        payload: DataTableCellUpdatePayload<TRow>,
    ) => void | Promise<void>;
    onRowUpdate?: (payload: DataTableRowUpdatePayload<TRow>) => void | Promise<void>;
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
    /**
     * When the row drawer is in `attachExisting` mode (toolbar `action: attach`), render
     * the BelongsToMany “pick existing” UI here. Omitted: default column field list (same as `create`/`add`).
     * Saved `draft` should include `relation_value` and optional `pivot` for server BTM sync.
     */
    renderRowDrawerAttachBody?: (
        ctx: DataTableRowDrawerAttachBodyRenderContext,
    ) => ReactNode;
    /**
     * When the table is relation-backed, the server may set this from the parent model’s
     * Eloquent relation (see `table_relation_type` in `form.json`). Omitted for non-relation tables.
     */
    tableRelationType?: FlatpackTableRelationType;
    /**
     * When set (embedded `type: table` on a form page), `type: relation` columns in the row
     * drawer load options from `GET .../embedded-table-relation-options`.
     */
    flatpackEntity?: string;
    /** Form field id for this table (same as the field key in `form.yaml`). */
    flatpackTableFieldId?: string;
};
