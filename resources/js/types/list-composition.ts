import type {
    FlatpackActionTarget,
    FlatpackActionUiMeta,
    FlatpackActionVisibilityMeta,
    FlatpackYamlButtonVariant,
} from '@/types/action-shared';
import type {
    FlatpackDataTableDefaultSort,
    FlatpackDataTableSelectOptionStatus,
    FlatpackSuccessRedirect,
} from '@/types/data-table';
import type { FlatpackListRowClickMode } from '@/types/list-shared';

/** List schema shape consumed by the React list page (already normalized by PHP). */

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
} & FlatpackActionTarget &
    FlatpackActionUiMeta<FlatpackSuccessRedirect | true>;

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
} & FlatpackActionTarget &
    FlatpackActionUiMeta<ListCompositionHeaderActionSuccessRedirect> &
    FlatpackActionVisibilityMeta;

/** Toolbar header actions keyed by arbitrary id (YAML map keys). */
export type FlatpackListCompositionListActionsYaml = Record<
    string,
    FlatpackListCompositionListHeaderActionYaml
>;

export type FlatpackListCompositionBulkActionYaml = {
    label: string;
    action: string;
    icon?: string;
    variant?: FlatpackYamlButtonVariant;
    success_message?: string;
    confirm?: boolean;
    success_redirect?: ListCompositionHeaderActionSuccessRedirect;
} & FlatpackActionVisibilityMeta;

export type FlatpackListCompositionBulkActionsYaml = Record<
    string,
    FlatpackListCompositionBulkActionYaml
>;

/** After PHP normalization: tab layout for list column visibility groups. */
export type FlatpackListTabPanelLayout = {
    id: string;
    label: string;
    icon?: string;
    scope?: string;
    reorderable?: boolean | string;
    reorderableColumn?: string;
    row_click?: FlatpackListRowClickMode;
    pagination?: boolean;
    default_sort?: FlatpackDataTableDefaultSort;
    columns?: FlatpackListCompositionColumnsYaml;
    filters?: FlatpackListCompositionFiltersYaml;
    bulk_actions?: FlatpackListCompositionBulkActionsYaml;
    column_ids: string[];
};

export type FlatpackListSchema = {
    name?: string;
    model?: string;
    icon?: string;
    nav_order?: number;
    reorderable?: boolean | string;
    reorderableColumn?: string;
    default_sort?: FlatpackDataTableDefaultSort;
    /** Row click behavior. Default `none`; `edit_page` navigates to edit route. */
    row_click?: FlatpackListRowClickMode;
    pagination?: boolean;
    showColumnsVisibility?: boolean;
    columns?: FlatpackListCompositionColumnsYaml;
    tab_panels?: FlatpackListTabPanelLayout[];
    filters?: FlatpackListCompositionFiltersYaml;
    actions?: FlatpackListCompositionListActionsYaml;
    bulk_actions?: FlatpackListCompositionBulkActionsYaml;
};
