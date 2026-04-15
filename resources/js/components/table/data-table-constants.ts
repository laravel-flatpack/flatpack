export const DATA_TABLE_DRAG_COLUMN_HEAD_CLASS = 'w-8 min-w-8 max-w-8 px-1';

export const DATA_TABLE_DRAG_COLUMN_CELL_CLASS = 'w-8 min-w-8 max-w-8 p-1';

export const DASHBOARD_TABLE_INPUT_CLASS =
    'h-8 min-w-0 w-full max-w-full border-transparent bg-transparent text-sm shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30';

export const DASHBOARD_TABLE_SELECT_TRIGGER_CLASS =
    'h-8 w-full min-w-0 max-w-full border-transparent bg-transparent shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate';

export const ACTION_MENU_LABEL_MAX_CHARS = 28;
export const DATA_TABLE_PAGE_SIZE_OPTIONS = [10, 20, 30, 40, 50] as const;
export const DATA_TABLE_EMPTY_RESULTS_LABEL = 'No results.';
export const DATA_TABLE_LABEL = 'Data table';
export const DATA_TABLE_SEARCH_PLACEHOLDER = 'Search…';

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
