import type { SortingState } from '@tanstack/react-table';
import { format } from 'date-fns';
import * as React from 'react';
import type {
    FlatpackDataTableServerFiltersState,
    FlatpackListServerPagination,
    FlatpackListServerSorting,
} from '@/types/data-table';

type PaginationState = {
    pageIndex: number;
    pageSize: number;
};

type UseDataTableServerStateOptions = {
    serverPagination?: FlatpackListServerPagination;
    serverSearch?: string;
    serverFilterValues?: FlatpackDataTableServerFiltersState;
    serverSorting?: FlatpackListServerSorting;
    onServerPaginationChange?: (
        page: number,
        perPage: number,
        search?: string,
        filters?: FlatpackDataTableServerFiltersState,
        sorting?: FlatpackListServerSorting,
    ) => void;
};

function normalizeServerFilterValues(
    values: FlatpackDataTableServerFiltersState | undefined,
): FlatpackDataTableServerFiltersState {
    if (!values) {
        return {};
    }
    const out: FlatpackDataTableServerFiltersState = {};
    for (const [key, value] of Object.entries(values)) {
        if (Array.isArray(value)) {
            out[key] = value.map((v) => String(v)).filter((v) => v !== '');
            continue;
        }
        if (value == null) {
            out[key] = null;
            continue;
        }
        out[key] = String(value);
    }
    return out;
}

function filterValuesEqual(
    a: FlatpackDataTableServerFiltersState,
    b: FlatpackDataTableServerFiltersState,
): boolean {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    for (const key of keys) {
        const av = a[key];
        const bv = b[key];
        if (Array.isArray(av) || Array.isArray(bv)) {
            if (
                !Array.isArray(av) ||
                !Array.isArray(bv) ||
                av.length !== bv.length
            ) {
                return false;
            }
            for (let i = 0; i < av.length; i++) {
                if (av[i] !== bv[i]) {
                    return false;
                }
            }
            continue;
        }
        if ((av ?? null) !== (bv ?? null)) {
            return false;
        }
    }
    return true;
}

function serverSortingFromState(
    sorting: SortingState,
): FlatpackListServerSorting {
    const first = sorting[0];
    if (!first) {
        return { sort_by: null, sort_direction: null };
    }
    return {
        sort_by: first.id,
        sort_direction: first.desc ? 'desc' : 'asc',
    };
}

function sortingStatesEqual(a: SortingState, b: SortingState): boolean {
    if (a.length !== b.length) {
        return false;
    }
    for (let i = 0; i < a.length; i++) {
        if (a[i]?.id !== b[i]?.id || a[i]?.desc !== b[i]?.desc) {
            return false;
        }
    }
    return true;
}

export function useDataTableServerState({
    serverPagination,
    serverSearch,
    serverFilterValues = {},
    serverSorting = { sort_by: null, sort_direction: null },
    onServerPaginationChange,
}: UseDataTableServerStateOptions) {
    const [globalFilter, setGlobalFilter] = React.useState(serverSearch ?? '');
    const [serverFilterState, setServerFilterState] =
        React.useState<FlatpackDataTableServerFiltersState>(() =>
            normalizeServerFilterValues(serverFilterValues),
        );
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [pagination, setPagination] = React.useState<PaginationState>({
        pageIndex: 0,
        pageSize: 10,
    });

    const serverPaginationState = React.useMemo(() => {
        if (serverPagination == null) {
            return null;
        }
        return {
            pageIndex: serverPagination.current_page - 1,
            pageSize: serverPagination.per_page,
        };
    }, [serverPagination]);

    const paginationState = serverPaginationState ?? pagination;

    React.useEffect(() => {
        if (serverPagination == null) {
            return;
        }
        setGlobalFilter(serverSearch ?? '');
    }, [serverPagination, serverSearch]);

    React.useEffect(() => {
        if (serverPagination == null) {
            return;
        }
        setServerFilterState(normalizeServerFilterValues(serverFilterValues));
    }, [serverFilterValues, serverPagination]);

    React.useEffect(() => {
        if (serverPagination == null) {
            return;
        }
        const nextSorting: SortingState =
            serverSorting.sort_by == null
                ? []
                : [
                      {
                          id: serverSorting.sort_by,
                          desc: serverSorting.sort_direction === 'desc',
                      },
                  ];
        setSorting((prev) =>
            sortingStatesEqual(prev, nextSorting) ? prev : nextSorting,
        );
    }, [serverPagination, serverSorting.sort_by, serverSorting.sort_direction]);

    const handlePaginationChange = React.useCallback(
        (updater: React.SetStateAction<PaginationState>) => {
            if (serverPagination != null && onServerPaginationChange != null) {
                const next =
                    typeof updater === 'function'
                        ? updater(paginationState)
                        : updater;
                onServerPaginationChange(
                    next.pageIndex + 1,
                    next.pageSize,
                    globalFilter,
                    serverFilterState,
                    serverSortingFromState(sorting),
                );
                return;
            }
            setPagination(updater);
        },
        [
            globalFilter,
            onServerPaginationChange,
            paginationState,
            serverFilterState,
            serverPagination,
            sorting,
        ],
    );

    const handleSortingChange = React.useCallback(
        (updater: React.SetStateAction<SortingState>) => {
            if (serverPagination != null && onServerPaginationChange != null) {
                const nextSorting =
                    typeof updater === 'function' ? updater(sorting) : updater;
                setSorting(nextSorting);
                onServerPaginationChange(
                    1,
                    paginationState.pageSize,
                    globalFilter,
                    serverFilterState,
                    serverSortingFromState(nextSorting),
                );
                return;
            }
            setSorting(updater);
        },
        [
            globalFilter,
            onServerPaginationChange,
            paginationState.pageSize,
            serverFilterState,
            serverPagination,
            sorting,
        ],
    );

    React.useEffect(() => {
        if (serverPagination == null || onServerPaginationChange == null) {
            return;
        }
        const normalizedServerSearch = serverSearch ?? '';
        const normalizedServerFilters =
            normalizeServerFilterValues(serverFilterValues);
        if (
            globalFilter === normalizedServerSearch &&
            filterValuesEqual(serverFilterState, normalizedServerFilters)
        ) {
            return;
        }
        const debounce = window.setTimeout(() => {
            onServerPaginationChange(
                1,
                paginationState.pageSize,
                globalFilter,
                serverFilterState,
                serverSortingFromState(sorting),
            );
        }, 250);

        return () => window.clearTimeout(debounce);
    }, [
        globalFilter,
        onServerPaginationChange,
        paginationState.pageSize,
        serverFilterState,
        serverFilterValues,
        serverPagination,
        serverSearch,
        sorting,
    ]);

    const setSingleServerFilter = React.useCallback(
        (filterId: string, value: string) => {
            setServerFilterState((prev) => ({
                ...prev,
                [filterId]: value === '' ? null : value,
            }));
        },
        [],
    );

    const toggleMultiServerFilterValue = React.useCallback(
        (filterId: string, optionValue: string) => {
            setServerFilterState((prev) => {
                const current = prev[filterId];
                const currentValues = Array.isArray(current)
                    ? current
                    : current
                      ? [current]
                      : [];
                const exists = currentValues.includes(optionValue);
                const nextValues = exists
                    ? currentValues.filter((value) => value !== optionValue)
                    : [...currentValues, optionValue];
                return {
                    ...prev,
                    [filterId]: nextValues.length > 0 ? nextValues : null,
                };
            });
        },
        [],
    );

    const setDateServerFilter = React.useCallback(
        (filterId: string, date?: Date) => {
            setServerFilterState((prev) => ({
                ...prev,
                [filterId]: date ? format(date, 'yyyy-MM-dd') : null,
            }));
        },
        [],
    );

    return {
        globalFilter,
        setGlobalFilter,
        serverFilterState,
        sorting,
        setSorting,
        paginationState,
        handlePaginationChange,
        handleSortingChange,
        setSingleServerFilter,
        toggleMultiServerFilterValue,
        setDateServerFilter,
    };
}
