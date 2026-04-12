import {
    AlertTriangleIcon,
    CircleCheckIcon,
    CircleXIcon,
    InfoIcon,
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
            className="size-3.5 shrink-0 text-green-600 dark:text-green-400"
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
    info: (
        <InfoIcon
            className="size-3.5 shrink-0 text-blue-600 dark:text-blue-400"
            aria-hidden
        />
    ),
};

/** Leading icon from option `status` (form selects, data table cells, column schema from PHP, etc.). */
export function selectOptionLeadingIcon(
    option: Pick<FlatpackDataTableColumnOption, 'status'> | undefined,
): React.ReactNode {
    const s = option?.status;
    if (s === undefined) {
        return null;
    }
    return SELECT_OPTION_STATUS_ICONS[s] ?? null;
}
