/**
 * Column schema for {@link DataTable} (Flatpack list views, demo `type=table`, etc.).
 * Mirrors the shape sent from `DemoController` / future list controllers.
 */
export type FlatpackDataTableColumnOption = {
    value: string;
    label: string;
    /** Optional semantic color for badge styling (e.g. `green`, `red`, `yellow`). */
    color?: string;
};

/** Row action button (e.g. edit / delete); `href` / `url` may include `{columnId}` placeholders. */
export type FlatpackDataTableActionButton = {
    label: string;
    icon?: string;
    href?: string;
    /** Alias for `href` (e.g. from older payloads). */
    url?: string;
};

export type FlatpackDataTableColumn = {
    id: string;
    label: string;
    /** Defaults to plain text when omitted. */
    type?: 'text' | 'select' | 'date' | 'actions';
    options?: FlatpackDataTableColumnOption[];
    /** When `type` is `actions`, keyed button definitions (order preserved in modern runtimes). */
    buttons?: Record<string, FlatpackDataTableActionButton>;
    /** PHP-style date format hint (e.g. `Y-m-d`); used for display trimming/parsing. */
    format?: string;
    timezone?: string;
    sortable?: boolean;
    searchable?: boolean;
    /** Start hidden; user can show via column picker. */
    invisible?: boolean;
};
