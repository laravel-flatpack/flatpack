import type { FlatpackListServerPagination } from '@/types/data-table';

/** Normalized from list.yaml `actions` (URLs already include `config('flatpack.prefix')`). */
export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    href: string;
    icon?: string;
    /**
     * Set server-side for each action (`primary` in YAML → `default`; omitted → `outline`).
     * Optional for older cached payloads; the list page falls back to `outline`.
     */
    variant?:
        | 'default'
        | 'outline'
        | 'secondary'
        | 'ghost'
        | 'destructive'
        | 'link';
};

export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    order?: number;
    /** Parsed list.yaml; may include `columns`, `actions`, `checkboxes`, etc. */
    schema?: Record<string, unknown> | null;
    /** Rows for the list table (Eloquent attributes matching list column keys). */
    records?: Record<string, unknown>[];
    /** Present on entity list routes with server-side pagination. */
    pagination?: FlatpackListServerPagination;
    /** Route prefix without leading/trailing slashes (e.g. `flatpack`). */
    flatpack_prefix?: string;
    /** Optional header buttons from list.yaml `actions`. */
    list_actions?: FlatpackListHeaderAction[];
};

export type FlatpackFormPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    record: string | null;
    mode: 'create' | 'edit';
    schema?: Record<string, unknown> | null;
};
