import type { FlatpackActionVariant as DataTableActionVariant } from '@/types/data-table';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

export type FlatpackActionConditionPredicate =
    | { 'form.dirty': boolean }
    | { 'form.mode_in': Array<'create' | 'edit'> }
    | { 'list.selection.min': number }
    | { 'list.search_present': boolean }
    | { 'list.filters_applied': boolean };

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
