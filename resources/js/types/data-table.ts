/**
 * Column schema for {@link DataTable} (Flatpack list views, demo `type=table`, etc.).
 * Mirrors the shape sent from `DemoController` / future list controllers.
 */
/** Drives the read-only select cell leading icon in {@link DataTable}. */
export type FlatpackDataTableSelectOptionStatus =
    | 'success'
    | 'pending'
    | 'warning'
    | 'error';

export type FlatpackDataTableColumnOption = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
};

/**
 * Row action entry for `type: 'actions'` columns.
 * - `action`: server-side action name (e.g. `edit`, `delete`); wired to backend handlers (Flatpack TODO).
 * - `href` / `url`: optional navigation; `{columnId}` placeholders are interpolated from the row.
 */
export type FlatpackDataTableActionButton = {
    label: string;
    icon?: string;
    /** Backend action key (e.g. from PHP `action`); used for future API / Inertia calls. */
    action?: string;
    href?: string;
    /** Alias for `href` (e.g. from older payloads). */
    url?: string;
};

export type FlatpackDataTableColumn = {
    id: string;
    label: string;
    /** Defaults to plain text when omitted. */
    type?: 'text' | 'select' | 'date' | 'actions' | 'badge';
    options?: FlatpackDataTableColumnOption[];
    /** When `type` is `actions`, keyed button definitions (order preserved in modern runtimes). */
    buttons?: Record<string, FlatpackDataTableActionButton>;
    /** PHP-style date format hint (e.g. `Y-m-d`); used for display trimming/parsing. */
    format?: string;
    timezone?: string;
    /** When `true`, the column header toggles sort (TanStack Table). Omitted or `false` = not sortable. */
    sortable?: boolean;
    searchable?: boolean;
    /** When true, cell is edited inline (text, select, or date by `type`). Ignored for `actions`. */
    editable?: boolean;
    /**
     * When true, the cell shows a link-style control that opens a drawer to edit the row
     * (same pattern as the dashboard data table header column). Suppresses inline editing for this column.
     */
    detailDrawer?: boolean;
    /** Start hidden; user can show via column picker. */
    invisible?: boolean;
};
