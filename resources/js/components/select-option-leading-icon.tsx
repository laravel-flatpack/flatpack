import { CircleIcon } from 'lucide-react';
import type * as React from 'react';
import { flatpackMenuIcons } from '@/components/lucide-menu-icon-registry';
import type {
    FlatpackDataTableColumnOption,
    FlatpackDataTableSelectOptionStatus,
} from '@/types/data-table';

const SELECT_OPTION_STATUS_COLORS: Record<
    FlatpackDataTableSelectOptionStatus,
    string
> = {
    success: 'text-green-600 dark:text-green-400',
    pending: 'text-sky-600 dark:text-sky-400',
    warning: 'text-amber-600 dark:text-amber-400',
    error: 'text-red-600 dark:text-red-400',
    info: 'text-blue-600 dark:text-blue-400',
};

export function selectOptionLeadingIcon(
    option: Pick<FlatpackDataTableColumnOption, 'status' | 'icon'> | undefined,
): React.ReactNode {
    const status = option?.status;
    const iconName =
        typeof option?.icon === 'string'
            ? option.icon.trim().toLowerCase()
            : '';
    const iconNode =
        iconName === ''
            ? undefined
            : flatpackMenuIcons[iconName as keyof typeof flatpackMenuIcons];
    const colorClass =
        status == null
            ? 'text-muted-foreground'
            : SELECT_OPTION_STATUS_COLORS[status];

    if (iconNode != null) {
        const IconNode = iconNode;
        return (
            <IconNode className={`size-3 shrink-0 ${colorClass}`} aria-hidden />
        );
    }

    if (status === undefined) {
        return null;
    }

    return (
        <CircleIcon
            className={`size-3 shrink-0 fill-current ${colorClass}`}
            aria-hidden
        />
    );
}
