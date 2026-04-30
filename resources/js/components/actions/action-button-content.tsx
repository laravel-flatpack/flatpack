import { LucideIconByName } from '@/components/icons/icons';
import { Spinner } from '@/components/ui/spinner';
import {
    formatShortcutHintCompact,
    type ParsedFlatpackShortcut,
} from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type {
    FlatpackActionButtonLabel,
    FlatpackActionVariant,
    FlatpackActionWithIcon,
    FlatpackActionWithVariant,
} from '@/types/flatpack-actions';

const SHORTCUT_CHIP_BASE =
    'ml-2 inline-flex max-w-full shrink-0 items-center rounded px-2 py-0.5 text-[0.6875rem] font-medium leading-none tracking-tight tabular-nums select-none group-disabled/button:opacity-50';

const SHORTCUT_CHIP_BY_VARIANT: Record<FlatpackActionVariant, string> = {
    default: 'bg-primary-foreground/15 text-primary-foreground',
    secondary: 'bg-secondary-foreground/12 text-secondary-foreground',
    destructive: 'bg-black/50 text-white dark:bg-black/30 dark:text-white',
    ghost: 'bg-muted text-muted-foreground shadow-none',
    link: 'bg-muted/80 text-muted-foreground shadow-none',
    outline:
        'bg-neutral-900 text-neutral-50 dark:border-white/15 dark:bg-neutral-800 dark:text-neutral-100',
};

export function actionVariant(
    action: FlatpackActionWithVariant,
    fallback: FlatpackActionVariant = 'outline',
): FlatpackActionVariant {
    return action.variant ?? fallback;
}

export function actionIconButtonClass(action: FlatpackActionWithIcon) {
    return action.icon ? 'inline-flex items-center gap-1.5' : undefined;
}

function shortcutChipClass(variant: FlatpackActionVariant): string {
    return cn(SHORTCUT_CHIP_BASE, SHORTCUT_CHIP_BY_VARIANT[variant]);
}

function ActionShortcut({
    shortcut,
    isMacPlatform,
    variant,
}: {
    shortcut?: ParsedFlatpackShortcut;
    isMacPlatform: boolean;
    variant: FlatpackActionVariant;
}) {
    if (!shortcut) {
        return null;
    }

    return (
        <span className={shortcutChipClass(variant)} aria-hidden>
            {formatShortcutHintCompact(shortcut, isMacPlatform)}
        </span>
    );
}

type ActionButtonContentProps = {
    action: FlatpackActionButtonLabel;
    isMacPlatform: boolean;
    variant: FlatpackActionVariant;
    shortcut?: ParsedFlatpackShortcut;
    showSpinner?: boolean;
    hideLabelOnMobileWhenIcon?: boolean;
};

export function ActionButtonContent({
    action,
    isMacPlatform,
    variant,
    shortcut,
    showSpinner = false,
    hideLabelOnMobileWhenIcon = false,
}: ActionButtonContentProps) {
    const hideLabelOnMobile = hideLabelOnMobileWhenIcon && Boolean(action.icon);

    return (
        <>
            {action.icon ? <LucideIconByName name={action.icon} /> : null}
            {showSpinner ? <Spinner className="size-4" /> : null}
            <span
                className={hideLabelOnMobile ? 'hidden sm:inline' : undefined}
            >
                {action.label}
            </span>
            {hideLabelOnMobile ? (
                <span className="sr-only">{action.label}</span>
            ) : null}
            <ActionShortcut
                shortcut={shortcut}
                isMacPlatform={isMacPlatform}
                variant={variant}
            />
        </>
    );
}
