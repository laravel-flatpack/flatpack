'use client';

import type { ComponentProps } from 'react';
import { ActionButton } from '@/components/actions/action-button';
import {
    buildDataTableRowActionPayload,
    isExternalRowActionHref,
    resolveRowActionInterpolatedHref,
} from '@/lib/data-table-row-action';
import type {
    DataTableRowActionPayload,
    FlatpackDataTableActionButton,
} from '@/types/data-table';

type ButtonSize = NonNullable<ComponentProps<typeof ActionButton>['size']>;

/**
 * Schema-driven table/grid row action: same rules as {@link ActionButton}, with interpolated
 * `href`, {@link buildDataTableRowActionPayload}, and optional `data-flatpack-action`.
 */
export function RowActionButton({
    button,
    row,
    onAction,
    size = 'default',
    className,
}: {
    button: FlatpackDataTableActionButton;
    row: Record<string, unknown>;
    onAction: (payload: DataTableRowActionPayload) => void;
    size?: ButtonSize;
    className?: string;
}) {
    const resolvedHref = resolveRowActionInterpolatedHref(button, row);
    const href = resolvedHref !== '' ? resolvedHref : undefined;
    const hrefMode =
        href != null
            ? isExternalRowActionHref(href)
                ? 'external'
                : 'inertia'
            : undefined;

    return (
        <ActionButton
            action={{
                label: button.label,
                icon: button.icon,
                variant: button.variant,
            }}
            isMacPlatform={false}
            size={size}
            className={className}
            href={href}
            hrefMode={hrefMode}
            nativeType="button"
            onClick={
                href != null
                    ? undefined
                    : () => {
                          onAction(buildDataTableRowActionPayload(button, row));
                      }
            }
            {...(button.action != null && button.action !== ''
                ? { 'data-flatpack-action': button.action }
                : {})}
        />
    );
}
