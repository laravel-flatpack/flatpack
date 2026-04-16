import type {
    FlatpackActionVariant,
    FlatpackDataTableBulkAction,
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
} from '@/types/data-table';

export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    icon?: string;
    variant?: FlatpackActionVariant;
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
    flatpack_prefix?: string;
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
};
