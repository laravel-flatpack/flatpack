import type {
    FlatpackActionVariant,
    FlatpackSuccessRedirect,
} from '@/types/data-table';
import type { FlatpackActionCondition } from '@/types/flatpack-actions';

export type FlatpackActionTarget =
    | { action: string; href?: never }
    | { href: string; action?: never };

/** YAML `variant` values include legacy `primary` before UI normalization. */
export type FlatpackYamlButtonVariant = FlatpackActionVariant | 'primary';

export type FlatpackActionVisibilityMeta = {
    enabled_if?: FlatpackActionCondition;
    visible_if?: FlatpackActionCondition;
    shortcut?: string;
};

export type FlatpackActionUiMeta<TRedirect = FlatpackSuccessRedirect | true> = {
    icon?: string;
    variant?: FlatpackYamlButtonVariant;
    success_message?: string;
    confirm?: boolean;
    success_redirect?: TRedirect;
};
