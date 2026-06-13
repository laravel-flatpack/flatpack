export const DATA_TABLE_DRAG_COLUMN_HEAD_CLASS = 'w-8 min-w-8 max-w-8 px-1';

export const DATA_TABLE_DRAG_COLUMN_CELL_CLASS = 'w-8 min-w-8 max-w-8 p-1';

/**
 * TanStack column id for bulk row selection checkboxes.
 * Must not equal a YAML column `id` (e.g. lists often use `id: select` for select cells).
 */
export const DATA_TABLE_ROW_SELECTION_COLUMN_ID =
    '__flatpack_row_selection__' as const;

export const DATA_TABLE_SELECT_COLUMN_HEAD_CLASS = 'w-8 min-w-8 max-w-8';

export const DATA_TABLE_SELECT_COLUMN_CELL_CLASS = 'w-8 min-w-8 max-w-8';

/**
 * Inner wrapper for each body cell. `min-h-8` matches `DASHBOARD_TABLE_INPUT_CLASS` /
 * `DASHBOARD_TABLE_SELECT_TRIGGER_CLASS` so read-only rows keep the same row height as
 * editable cells.
 */
export const DATA_TABLE_CELL_CONTENT_CLASS =
    'flex min-h-8 w-full min-w-0 items-center gap-2';

export const DASHBOARD_TABLE_INPUT_CLASS =
    'h-8 min-w-0 w-full max-w-full border-transparent bg-transparent text-sm shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30';

export const DASHBOARD_TABLE_SELECT_TRIGGER_CLASS =
    'h-8 w-full min-w-0 max-w-full border-transparent bg-transparent shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate';

export const ACTION_MENU_LABEL_MAX_CHARS = 28;
export const DATA_TABLE_PAGE_SIZE_OPTIONS = [5, 10, 20, 30, 40, 50] as const;
export const DATA_TABLE_EMPTY_RESULTS_LABEL = 'No results.';
export const DATA_TABLE_LABEL = 'Data table';
export const DATA_TABLE_SEARCH_PLACEHOLDER = 'Search…';

export const GRID_WIDGET_PAGE_SIZE_OPTIONS = [6, 9, 24] as const;

/** Clicks on these targets do not trigger “row → open record” navigation. */
export const DATA_TABLE_ROW_CLICK_IGNORE_SELECTOR =
    'a,button,input,select,textarea,[role="button"],[role="checkbox"],[role="menuitem"],[data-no-row-click]';

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
