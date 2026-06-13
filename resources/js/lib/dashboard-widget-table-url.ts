import type { FlatpackListServerSorting } from '@/types/data-table';

function stripWidgetTableQueryParams(search: string): string {
    const trimmed = search.startsWith('?') ? search.slice(1) : search;
    const parts = trimmed.split('&').filter(Boolean);
    const kept = parts.filter((segment) => {
        const key = segment.split('=')[0] ?? '';
        const decoded = decodeURIComponent(key);
        return /.+_(page|limit|q|sort_by|sort_dir)$/.test(decoded) === false;
    });
    return kept.length > 0 ? `?${kept.join('&')}` : '';
}

type WidgetTableQueryBuckets = {
    pages: Record<string, number>;
    searches: Record<string, string>;
    sorts: Record<string, FlatpackListServerSorting>;
    perPages: Record<string, number>;
};

function parseWidgetTableQuery(search: string): WidgetTableQueryBuckets {
    const buckets: WidgetTableQueryBuckets = {
        pages: {},
        searches: {},
        sorts: {},
        perPages: {},
    };
    const trimmed = search.startsWith('?') ? search.slice(1) : search;
    for (const part of trimmed.split('&').filter(Boolean)) {
        const eq = part.indexOf('=');
        if (eq < 0) {
            continue;
        }
        const rawKey = part.slice(0, eq);
        const rawVal = part.slice(eq + 1);
        const key = decodeURIComponent(rawKey);
        const value = decodeURIComponent(rawVal);
        const namedPageM = key.match(/^(.+)_page$/);
        if (namedPageM) {
            const n = Number.parseInt(value, 10);
            if (!Number.isNaN(n) && n >= 1) {
                buckets.pages[namedPageM[1]] = n;
            }
            continue;
        }
        const namedSearchM = key.match(/^(.+)_q$/);
        if (namedSearchM) {
            buckets.searches[namedSearchM[1]] = value;
            continue;
        }
        const namedPerM = key.match(/^(.+)_limit$/);
        if (namedPerM) {
            const n = Number.parseInt(value, 10);
            if (!Number.isNaN(n) && n >= 1) {
                buckets.perPages[namedPerM[1]] = n;
            }
            continue;
        }
        const namedSortByM = key.match(/^(.+)_sort_by$/);
        if (namedSortByM) {
            const id = namedSortByM[1];
            const cur = buckets.sorts[id] ?? {
                sort_by: null,
                sort_direction: null,
            };
            cur.sort_by = value.trim() === '' ? null : value;
            buckets.sorts[id] = cur;
            continue;
        }
        const namedSortDirM = key.match(/^(.+)_sort_dir$/);
        if (namedSortDirM) {
            const id = namedSortDirM[1];
            const cur = buckets.sorts[id] ?? {
                sort_by: null,
                sort_direction: null,
            };
            cur.sort_direction =
                value === 'asc' || value === 'desc' ? value : null;
            buckets.sorts[id] = cur;
        }
    }
    return buckets;
}

/**
 * Merges dashboard model-backed widget table state into the current URL query so Inertia
 * can reload with {@see ResolvesWidgets} request parsing.
 */
export function buildDashboardWidgetTableVisitSearch(
    currentSearch: string,
    widgetId: string,
    update: {
        page: number;
        perPage: number;
        search: string;
        sorting: FlatpackListServerSorting;
    },
): string {
    const base = stripWidgetTableQueryParams(currentSearch);
    const buckets = parseWidgetTableQuery(currentSearch);

    buckets.pages[widgetId] = update.page;
    buckets.searches[widgetId] = update.search;
    if (
        update.sorting.sort_by != null &&
        String(update.sorting.sort_by).trim() !== ''
    ) {
        buckets.sorts[widgetId] = {
            sort_by: update.sorting.sort_by,
            sort_direction: update.sorting.sort_direction,
        };
    } else {
        delete buckets.sorts[widgetId];
    }
    buckets.perPages[widgetId] = update.perPage;

    const params = new URLSearchParams(
        base.startsWith('?') ? base.slice(1) : base,
    );
    for (const [id, page] of Object.entries(buckets.pages)) {
        if (page > 1) {
            params.set(`${id}_page`, String(page));
        }
    }
    for (const [id, term] of Object.entries(buckets.searches)) {
        if (term.trim() !== '') {
            params.set(`${id}_q`, term);
        }
    }
    for (const [id, sort] of Object.entries(buckets.sorts)) {
        if (sort.sort_by != null && sort.sort_by.trim() !== '') {
            params.set(`${id}_sort_by`, sort.sort_by);
            params.set(
                `${id}_sort_dir`,
                sort.sort_direction === 'asc' || sort.sort_direction === 'desc'
                    ? sort.sort_direction
                    : 'asc',
            );
        }
    }
    for (const [id, pp] of Object.entries(buckets.perPages)) {
        params.set(`${id}_limit`, String(pp));
    }
    const qs = params.toString();
    return qs === '' ? '' : `?${qs}`;
}
