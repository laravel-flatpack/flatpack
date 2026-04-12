import {
    AlertTriangleIcon,
    CircleCheckIcon,
    CircleXIcon,
    LoaderIcon,
} from 'lucide-react';
import type * as React from 'react';
import type {
    FlatpackDataTableColumnOption,
    FlatpackDataTableSelectOptionStatus,
} from '@/types/data-table';

const SELECT_OPTION_STATUS_ICONS: Record<
    FlatpackDataTableSelectOptionStatus,
    React.ReactElement
> = {
    success: (
        <CircleCheckIcon
            className="size-3.5 shrink-0 fill-green-500 dark:fill-green-400"
            aria-hidden
        />
    ),
    pending: (
        <LoaderIcon className="size-3.5 shrink-0 opacity-80" aria-hidden />
    ),
    warning: (
        <AlertTriangleIcon
            className="size-3.5 shrink-0 text-amber-600 dark:text-amber-400"
            aria-hidden
        />
    ),
    error: (
        <CircleXIcon
            className="size-3.5 shrink-0 text-red-600 dark:text-red-400"
            aria-hidden
        />
    ),
};

/** Leading icon from option `status` (set in column schema, e.g. PHP). */
export function selectOptionLeadingIcon(
    option: FlatpackDataTableColumnOption | undefined,
): React.ReactNode {
    const s = option?.status;
    if (s === undefined) {
        return null;
    }
    return SELECT_OPTION_STATUS_ICONS[s] ?? null;
}
