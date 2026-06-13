import type {
    FlatpackActionTarget,
    FlatpackActionVisibilityMeta,
} from '@/types/action-shared';
import type {
    FlatpackActionVariant,
    FlatpackDataTableBulkAction,
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
    FlatpackSuccessRedirect,
} from '@/types/data-table';
import type { FlatpackFormSchema } from '@/types/form-composition';
import type { FlatpackListSchema } from '@/types/list-composition';
import type { FlatpackWidgetsCompositionSchema } from '@/types/widgets-composition';

export type { FlatpackFormSchema, FlatpackListSchema };

export type FlatpackListHeaderAction = {
    id: string;
    label: string;
    icon?: string;
    shortcut?: string;
    variant?: FlatpackActionVariant;
    /** True when YAML references an {@code action} not registered in {@code flatpack.actions} (inline toolbar rows only). */
    handler_missing?: boolean;
    /** When true, default intent for implicit form submit (Enter / primary CTA). */
    primary?: boolean;
    success_message?: string;
    confirm?: boolean;
    /** Form-page only: submit current form values when true (default false). */
    submit?: boolean;
    success_redirect?: FlatpackSuccessRedirect;
} & FlatpackActionVisibilityMeta &
    FlatpackActionTarget;

/** Props for `/flatpack` dashboard (Inertia `dashboard` page). */
export type FlatpackDashboardPageProps = {
    model?: string;
    model_key?: string;
    widgets?: FlatpackWidgetsCompositionSchema['widgets'];
    schema?: FlatpackWidgetsCompositionSchema | null;
    /** Non-sensitive debug messages from composition normalization (APP_DEBUG). */
    composition_debug?: string[];
};

export type FlatpackListPageProps = {
    entity: string;
    name?: string;
    model?: string;
    model_key?: string;
    icon?: string;
    nav_order?: number;
    schema?: FlatpackListSchema | null;
    records?: Record<string, unknown>[];
    pagination?: FlatpackListServerPagination;
    search_term?: string;
    active_tab?: string | null;
    filters?: FlatpackDataTableFilter[];
    filter_values?: FlatpackDataTableServerFiltersState;
    sorting?: FlatpackListServerSorting;
    list_actions?: FlatpackListHeaderAction[];
    bulk_actions?: FlatpackDataTableBulkAction[];
    widgets?: FlatpackWidgetsCompositionSchema['widgets'];
    widgets_schema?: FlatpackWidgetsCompositionSchema | null;
    /** Non-sensitive debug messages from composition normalization (APP_DEBUG). */
    composition_debug?: string[];
};

/**
 * Inertia form page props (server). Custom embedded `type: table` toolbar actions are wired
 * client-side via `FormFields` or `EmbeddedTableToolbarProvider` (see `resources/js/components/shell/form/`).
 */
export type FlatpackFormPageProps = {
    entity: string;
    name?: string;
    model?: string;
    model_key?: string;
    icon?: string;
    record: string | null;
    mode: 'create' | 'edit';
    schema?: FlatpackFormSchema | null;
    values?: Record<string, unknown>;
    form_actions?: FlatpackListHeaderAction[];
    widgets?: FlatpackWidgetsCompositionSchema['widgets'];
    widgets_schema?: FlatpackWidgetsCompositionSchema | null;
    sidebar_widgets?: FlatpackWidgetsCompositionSchema['widgets'];
    sidebar_widgets_schema?: FlatpackWidgetsCompositionSchema | null;
    /** Non-sensitive debug messages from composition normalization (APP_DEBUG). */
    composition_debug?: string[];
};
