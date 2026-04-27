import type {
    FlatpackActionVariant,
    FlatpackDataTableBulkAction,
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
    FlatpackSuccessRedirect,
} from '@/types/data-table';
import type { FlatpackActionCondition } from '@/types/flatpack-actions';
import type { FlatpackFormCompositionSchema } from '@/types/form-composition';
import type { FlatpackListCompositionSchema } from '@/types/list-composition';

export type { FlatpackFormCompositionSchema, FlatpackListCompositionSchema };

export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    icon?: string;
    shortcut?: string;
    variant?: FlatpackActionVariant;
    /** When true, default intent for implicit form submit (Enter / primary CTA). */
    primary?: boolean;
    success_message?: string;
    confirm?: boolean;
    /** Form-page only: submit current form values when true (default false). */
    submit?: boolean;
    success_redirect?: FlatpackSuccessRedirect;
    enabled_if?: FlatpackActionCondition;
    visible_if?: FlatpackActionCondition;
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
    active_tab?: string | null;
    filters?: FlatpackDataTableFilter[];
    filter_values?: FlatpackDataTableServerFiltersState;
    sorting?: FlatpackListServerSorting;
    list_actions?: FlatpackListHeaderAction[];
    bulk_actions?: FlatpackDataTableBulkAction[];
    /** Non-sensitive debug messages when composition YAML was sanitized (APP_DEBUG). */
    composition_debug?: string[];
};

/**
 * Inertia form page props (server). Custom embedded `type: table` toolbar actions are wired
 * client-side via `FlatpackFormFields` or `FlatpackEmbeddedTableToolbarProvider` (see `resources/js/components/flatpack-form/`).
 */
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
