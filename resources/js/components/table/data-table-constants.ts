/** Same look as dashboard `DashboardDataTable` target/limit inputs. */
export const DASHBOARD_TABLE_INPUT_CLASS =
    'h-8 min-w-0 w-full max-w-full border-transparent bg-transparent text-sm shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30';

export const DASHBOARD_TABLE_SELECT_TRIGGER_CLASS =
    'h-8 w-full min-w-0 max-w-full border-transparent bg-transparent shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate';

/** Max characters before appending `...` in row action menu labels (keeps one-line layout). */
export const ACTION_MENU_LABEL_MAX_CHARS = 28;

export function truncateActionMenuLabel(
    label: string,
    maxLen = ACTION_MENU_LABEL_MAX_CHARS,
): string {
    if (label.length <= maxLen) {
        return label;
    }
    const take = Math.max(0, maxLen - 3);
    return `${label.slice(0, take)}...`;
}
