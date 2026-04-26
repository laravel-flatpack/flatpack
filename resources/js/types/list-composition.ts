import type {
    FlatpackActionVariant,
    FlatpackDataTableSelectOptionStatus,
    FlatpackSuccessRedirect,
} from '@/types/data-table';
import type { FlatpackActionCondition } from '@/types/flatpack-actions';

/**
 * Raw list composition from `list.yaml` (decoded JSON). Parity with `resources/schema/list.json`.
 * These are pre-normalization shapes; see `list-schema.ts` and `useFlatpackList` for runtime types.
 */

/** YAML `variant` matches `resources/schema/list.json` `buttonVariant` (includes `primary` before UI normalization). */
type ListCompositionButtonVariantYaml = FlatpackActionVariant | 'primary';

type ListCompositionColumnSharedYaml = {
    id?: string;
    sortable?: boolean;
    searchable?: boolean;
    editable?: boolean;
    detailDrawer?: boolean;
    invisible?: boolean;
    truncate?: number;
    /** Full row-drawer edit field override for embedded form tables. */
    edit_form_field?: {
        type: string;
        [key: string]: unknown;
    };
    /** CamelCase alias of {@link edit_form_field}. */
    editFormField?: {
        type: string;
        [key: string]: unknown;
    };
};

type ListCompositionColumnOptionYaml = {
    value: string;
    label: string;
    status?: FlatpackDataTableSelectOptionStatus;
    icon?: string;
};

export type FlatpackListCompositionColumnOptionsYaml =
    | ListCompositionColumnOptionYaml[]
    | Record<string, string>;

/** Row/column action button as in list column `actions` or form `table` field `actions`. */
export type FlatpackListCompositionColumnActionButtonYaml = {
    label: string;
} & ({ action: string; href?: never } | { href: string; action?: never }) & {
        icon?: string;
        variant?: ListCompositionButtonVariantYaml;
        success_redirect?: FlatpackSuccessRedirect | true;
        success_message?: string;
        confirm?: boolean;
    };

type ListCompositionHeaderActionSuccessRedirect =
    | FlatpackSuccessRedirect
    | true;

export type FlatpackListCompositionColumnSelectYaml =
    ListCompositionColumnSharedYaml & {
        type: 'select';
        label: string;
        options: FlatpackListCompositionColumnOptionsYaml;
    };

export type FlatpackListCompositionColumnRelationYaml =
    ListCompositionColumnSharedYaml & {
        type: 'relation';
        label: string;
        relation: string;
        relation_name?: string;
        relation_value?: string;
        relationName?: string;
        relationValue?: string;
        options?: FlatpackListCompositionColumnOptionsYaml;
    };

export type FlatpackListCompositionColumnActionsYaml =
    ListCompositionColumnSharedYaml & {
        type: 'actions';
        label: string;
        actions: FlatpackListCompositionColumnActionButtonYaml[];
    };

export type FlatpackListCompositionColumnDateYaml =
    ListCompositionColumnSharedYaml & {
        type: 'date' | 'datetime';
        label: string;
        format?: string;
        timezone?: string;
    };

/** Text-like columns; `type` may be omitted (runtime defaults to text). */
export type FlatpackListCompositionColumnGenericYaml =
    ListCompositionColumnSharedYaml & {
        label: string;
        type?: 'text' | 'badge' | 'status';
        format?: string;
        timezone?: string;
    };

export type FlatpackListCompositionColumnYaml =
    | FlatpackListCompositionColumnSelectYaml
    | FlatpackListCompositionColumnRelationYaml
    | FlatpackListCompositionColumnActionsYaml
    | FlatpackListCompositionColumnDateYaml
    | FlatpackListCompositionColumnGenericYaml;

export type FlatpackListCompositionColumnsYaml =
    | FlatpackListCompositionColumnYaml[]
    | Record<string, FlatpackListCompositionColumnYaml>;

export type FlatpackListCompositionFilterYaml =
    | {
          type: 'select';
          options: FlatpackListCompositionColumnOptionsYaml;
          label?: string;
          placeholder?: string;
          multiple?: boolean;
      }
    | {
          type: 'date';
          label?: string;
          placeholder?: string;
          mode?: 'exact' | 'from';
      }
    /** Overrides when filter type comes from the column (`filterOverrides` branch). */
    | {
          label?: string;
          placeholder?: string;
      };

export type FlatpackListCompositionFiltersYaml = Record<
    string,
    FlatpackListCompositionFilterYaml
>;

export type FlatpackListCompositionListHeaderActionYaml = {
    label: string;
} & ({ action: string; href?: never } | { href: string; action?: never }) & {
        icon?: string;
        variant?: ListCompositionButtonVariantYaml;
        success_message?: string;
        confirm?: boolean;
        success_redirect?: ListCompositionHeaderActionSuccessRedirect;
        enabled_if?: FlatpackActionCondition;
        visible_if?: FlatpackActionCondition;
        shortcut?: string;
    };

/** Toolbar header actions keyed by arbitrary id (YAML map keys). */
export type FlatpackListCompositionListActionsYaml = Record<
    string,
    FlatpackListCompositionListHeaderActionYaml
>;

export type FlatpackListCompositionBulkActionYaml = {
    label: string;
    action: string;
    icon?: string;
    variant?: ListCompositionButtonVariantYaml;
    success_message?: string;
    confirm?: boolean;
    success_redirect?: ListCompositionHeaderActionSuccessRedirect;
    enabled_if?: FlatpackActionCondition;
    visible_if?: FlatpackActionCondition;
};

export type FlatpackListCompositionBulkActionsYaml = Record<
    string,
    FlatpackListCompositionBulkActionYaml
>;

/**
 * Entity list composition from list.yaml (decoded JSON). Structural contract: `resources/schema/list.json`.
 *
 * Index signature keeps forward-compatible with YAML additions; known keys match the schema file.
 */
export type FlatpackListCompositionTabPanelYaml = {
    label: string;
    icon?: string;
    columns: FlatpackListCompositionColumnsYaml;
};

export type FlatpackListCompositionTabsYaml = Record<
    string,
    FlatpackListCompositionTabPanelYaml
>;

/** After PHP normalization: tab layout for list column visibility groups. */
export type FlatpackListTabPanelLayout = {
    id: string;
    label: string;
    icon?: string;
    column_ids: string[];
};

export type FlatpackListCompositionSchema = {
    [key: string]: unknown;
    name?: string;
    model?: string;
    icon?: string;
    nav_order?: number;
    reorderable?: boolean | string;
    /** When false, rows do not open the edit page. When a string, names the row field used as the record id in the edit URL. Omit or true: default (server `model_key`, else `id`). */
    row_click_edit?: boolean | string;
    columns?: FlatpackListCompositionColumnsYaml;
    tabs?: FlatpackListCompositionTabsYaml;
    tab_panels?: FlatpackListTabPanelLayout[];
    filters?: FlatpackListCompositionFiltersYaml;
    actions?: FlatpackListCompositionListActionsYaml;
    bulk_actions?: FlatpackListCompositionBulkActionsYaml;
};
