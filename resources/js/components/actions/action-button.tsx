'use client';

import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { LucideIconByName } from '@/components/icons/icons';
import { Button } from '@/components/ui/button';
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

type ActionButtonLabelProps = {
    action: FlatpackActionButtonLabel & FlatpackActionWithIcon;
    isMacPlatform: boolean;
    variant: FlatpackActionVariant;
    shortcut?: ParsedFlatpackShortcut;
    showSpinner?: boolean;
    hideLabelOnMobileWhenIcon?: boolean;
};

function ActionButtonLabel({
    action,
    isMacPlatform,
    variant,
    shortcut,
    showSpinner = false,
    hideLabelOnMobileWhenIcon = false,
}: ActionButtonLabelProps) {
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

function isExternalHttpHref(href: string): boolean {
    return /^https?:\/\//i.test(href);
}

export type ActionButtonProps = {
    action: FlatpackActionButtonLabel &
        FlatpackActionWithIcon &
        Partial<FlatpackActionWithVariant>;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    size?: NonNullable<ComponentProps<typeof Button>['size']>;
    className?: string;
    disabled?: boolean;
    /** When set, renders navigation (Inertia {@link Link} or external `<a>`). */
    href?: string;
    /**
     * How `href` is opened. Defaults to `external` for `http(s):` URLs, otherwise Inertia
     * {@link Link}.
     */
    hrefMode?: 'inertia' | 'external';
    showSpinner?: boolean;
    hideLabelOnMobileWhenIcon?: boolean;
    nativeType?: 'button' | 'submit';
    form?: string;
    onClick?: ComponentProps<'button'>['onClick'];
    variant?: FlatpackActionVariant;
    'data-flatpack-action-id'?: string;
    'data-flatpack-action'?: string;
};

/**
 * Flatpack toolbar / header action: {@link Button} (or link-styled button) with label, optional
 * icon, shortcut chip, and spinner — shared by form actions, list actions, and row actions.
 */
export function ActionButton({
    action,
    isMacPlatform,
    shortcut,
    size = 'lg',
    className,
    disabled = false,
    href,
    hrefMode,
    showSpinner = false,
    hideLabelOnMobileWhenIcon = false,
    nativeType = 'button',
    form,
    onClick,
    variant: variantProp,
    'data-flatpack-action-id': dataFlatpackActionId,
    'data-flatpack-action': dataFlatpackAction,
}: ActionButtonProps) {
    const variant = variantProp ?? actionVariant(action);
    const mergedClassName = cn(actionIconButtonClass(action), className);
    const labelProps: ActionButtonLabelProps = {
        action,
        isMacPlatform,
        variant,
        shortcut,
        showSpinner,
        hideLabelOnMobileWhenIcon,
    };
    const inner = <ActionButtonLabel {...labelProps} />;
    const dataProps = {
        ...(dataFlatpackActionId != null
            ? { 'data-flatpack-action-id': dataFlatpackActionId }
            : {}),
        ...(dataFlatpackAction != null
            ? { 'data-flatpack-action': dataFlatpackAction }
            : {}),
    };

    if (href != null && href !== '') {
        const mode =
            hrefMode ?? (isExternalHttpHref(href) ? 'external' : 'inertia');
        if (disabled) {
            return (
                <Button
                    type="button"
                    size={size}
                    variant={variant}
                    disabled
                    className={mergedClassName}
                    {...dataProps}
                >
                    {inner}
                </Button>
            );
        }
        if (mode === 'external') {
            return (
                <Button
                    type="button"
                    size={size}
                    variant={variant}
                    className={mergedClassName}
                    asChild
                    {...dataProps}
                >
                    <a href={href} target="_blank" rel="noopener noreferrer">
                        {inner}
                    </a>
                </Button>
            );
        }
        return (
            <Button
                type="button"
                size={size}
                variant={variant}
                className={mergedClassName}
                asChild
                {...dataProps}
            >
                <Link href={href}>{inner}</Link>
            </Button>
        );
    }

    return (
        <Button
            type={nativeType}
            form={form}
            size={size}
            variant={variant}
            disabled={disabled}
            className={mergedClassName}
            onClick={onClick}
            {...dataProps}
        >
            {inner}
        </Button>
    );
}
