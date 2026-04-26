import type { FlatpackActionVariant as DataTableActionVariant } from '@/types/data-table';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

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
