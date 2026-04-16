import type {
    FlatpackActionVariant,
    FlatpackDataTableBulkAction,
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
    FlatpackSuccessRedirect,
} from '@/types/data-table';

export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    icon?: string;
    variant?: FlatpackActionVariant;
    success_message?: string;
    confirm?: boolean;
    success_redirect?: FlatpackSuccessRedirect;
    /** When true, action stays disabled until the form has unsaved changes (form pages). */
    disable_until_dirty?: boolean;
} & ({ href: string; action?: never } | { action: string; href?: never });

export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    model_key?: string;
    icon?: string;
    order?: number;
    schema?: Record<string, unknown> | null;
    records?: Record<string, unknown>[];
    pagination?: FlatpackListServerPagination;
    search_term?: string;
    filters?: FlatpackDataTableFilter[];
    filter_values?: FlatpackDataTableServerFiltersState;
    sorting?: FlatpackListServerSorting;
    list_actions?: FlatpackListHeaderAction[];
    bulk_actions?: FlatpackDataTableBulkAction[];
};

export type FlatpackFormPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    record: string | null;
    mode: 'create' | 'edit';
    schema?: Record<string, unknown> | null;
    values?: Record<string, unknown>;
    form_actions?: FlatpackListHeaderAction[];
};
