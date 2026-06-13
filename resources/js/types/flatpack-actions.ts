import type { FlatpackActionVariant as DataTableActionVariant } from '@/types/data-table';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

export type FlatpackActionConditionPredicate =
    | { 'form.dirty': boolean }
    | { 'form.mode_in': Array<'create' | 'edit'> }
    | { 'list.selection.min': number }
    | { 'list.search_present': boolean }
    | { 'list.filters_applied': boolean }
    | { 'form.field_eq': { field: string; value: unknown } }
    | {
          'form.field_in': {
              field: string;
              values: Array<boolean | number | string | null>;
          };
      }
    | { 'form.field_truthy': { field: string } }
    | { 'form.field_present': { field: string } }
    | { 'form.field_null': { field: string } };

export type FlatpackActionCondition = {
    all?: FlatpackActionConditionPredicate[];
    any?: FlatpackActionConditionPredicate[];
    message?: string;
};

export type FlatpackActionWithVariant = Pick<
    FlatpackListHeaderAction,
    'variant'
>;

export type FlatpackActionWithIcon = Pick<FlatpackListHeaderAction, 'icon'>;

export type FlatpackActionButtonLabel = Pick<
    FlatpackListHeaderAction,
    'label' | 'icon'
>;

export type FlatpackListSubmitAction = Extract<
    FlatpackListHeaderAction,
    { action: string }
>;

export type FlatpackActionVariant = DataTableActionVariant;
