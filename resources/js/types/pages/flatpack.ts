import type {
    FlatpackActionVariant,
    FlatpackDataTableBulkAction,
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
    FlatpackSuccessRedirect,
} from '@/types/data-table';
import type { FlatpackFormCompositionSchema } from '@/types/form-composition';
import type { FlatpackListCompositionSchema } from '@/types/list-composition';

export type { FlatpackFormCompositionSchema, FlatpackListCompositionSchema };

export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    icon?: string;
    shortcut?: string;
    variant?: FlatpackActionVariant;
    success_message?: string;
    confirm?: boolean;
    success_redirect?: FlatpackSuccessRedirect;
    /** When true, action stays disabled until the form has unsaved changes (form pages). */
    disable_until_dirty?: boolean;
} & ({ href: string; action?: never } | { action: string; href?: never });

/** Props for `/flatpack` dashboard (Inertia `dashboard` page). */
export type FlatpackDashboardPageProps = {
    schema?: Record<string, unknown> | null;
    composition_debug?: string[];
};

export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    model_key?: string;
    icon?: string;
    nav_order?: number;
    schema?: FlatpackListCompositionSchema | null;
    records?: Record<string, unknown>[];
    pagination?: FlatpackListServerPagination;
    search_term?: string;
    filters?: FlatpackDataTableFilter[];
    filter_values?: FlatpackDataTableServerFiltersState;
    sorting?: FlatpackListServerSorting;
    list_actions?: FlatpackListHeaderAction[];
    bulk_actions?: FlatpackDataTableBulkAction[];
    /** Non-sensitive debug messages when composition YAML was sanitized (APP_DEBUG). */
    composition_debug?: string[];
};

export type FlatpackFormPageProps = {
    entity: string;
    name?: string;
    model?: string;
    icon?: string;
    record: string | null;
    mode: 'create' | 'edit';
    schema?: FlatpackFormCompositionSchema | null;
    values?: Record<string, unknown>;
    form_actions?: FlatpackListHeaderAction[];
    /** Non-sensitive debug messages when composition YAML was sanitized (APP_DEBUG). */
    composition_debug?: string[];
};
