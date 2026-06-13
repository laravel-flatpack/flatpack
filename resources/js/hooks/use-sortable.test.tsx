import { act, renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { useSortable } from '@/hooks/use-sortable';

describe('useSortable', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it('sorts items by sort column on initialization', () => {
        const { result } = renderHook(() =>
            useSortable(
                [
                    { id: 2, sort_order: 2, name: 'B' },
                    { id: 1, sort_order: 1, name: 'A' },
                ],
                {
                    endpoint: (item) => `/reorder/${item.id}`,
                    sortColumn: 'sort_order',
                },
            ),
        );

        expect(result.current.items.map((item) => item.id)).toEqual([1, 2]);
    });

    it('optimistically reorders and reindexes values', () => {
        const { result } = renderHook(() =>
            useSortable(
                [
                    { id: 1, sort_order: 1, name: 'A' },
                    { id: 2, sort_order: 2, name: 'B' },
                    { id: 3, sort_order: 3, name: 'C' },
                ],
                {
                    endpoint: () => '',
                    sortColumn: 'sort_order',
                },
            ),
        );

        act(() => {
            result.current.handleReorder(3, 1);
        });

        expect(result.current.items.map((item) => item.id)).toEqual([3, 1, 2]);
        expect(result.current.items.map((item) => item.sort_order)).toEqual([
            1, 2, 3,
        ]);
    });

    it('rolls back and triggers onError when persistence fails', async () => {
        const onError = vi.fn();
        const fetchMock = vi.fn().mockResolvedValue({ ok: false } as Response);
        vi.stubGlobal('fetch', fetchMock);

        const { result } = renderHook(() =>
            useSortable(
                [
                    { id: 1, sort_order: 1, name: 'A' },
                    { id: 2, sort_order: 2, name: 'B' },
                    { id: 3, sort_order: 3, name: 'C' },
                ],
                {
                    endpoint: (item) => `/reorder/${item.id}`,
                    sortColumn: 'sort_order',
                    onError,
                },
            ),
        );

        act(() => {
            result.current.handleReorder(3, 1);
        });

        expect(result.current.items.map((item) => item.id)).toEqual([3, 1, 2]);

        await act(async () => {
            await Promise.resolve();
        });

        expect(result.current.items.map((item) => item.id)).toEqual([1, 2, 3]);
        expect(onError).toHaveBeenCalledTimes(1);
    });

    it('toggles isReordering while request is in-flight', async () => {
        let resolveFetch: ((value: Response) => void) | null = null;
        const fetchMock = vi.fn().mockImplementation(
            () =>
                new Promise<Response>((resolve) => {
                    resolveFetch = resolve;
                }),
        );
        vi.stubGlobal('fetch', fetchMock);

        const { result } = renderHook(() =>
            useSortable(
                [
                    { id: 1, sort_order: 1, name: 'A' },
                    { id: 2, sort_order: 2, name: 'B' },
                ],
                {
                    endpoint: (item) => `/reorder/${item.id}`,
                    sortColumn: 'sort_order',
                },
            ),
        );

        act(() => {
            result.current.handleReorder(2, 1);
        });
        expect(result.current.isReordering).toBe(true);

        await act(async () => {
            resolveFetch?.({ ok: true } as Response);
            await Promise.resolve();
        });
        expect(result.current.isReordering).toBe(false);
    });

    it('keeps sort values when reindexOnReorder is false', () => {
        const { result } = renderHook(() =>
            useSortable(
                [
                    { id: 1, sort_order: 100, name: 'A' },
                    { id: 2, sort_order: 200, name: 'B' },
                    { id: 3, sort_order: 300, name: 'C' },
                ],
                {
                    endpoint: () => '',
                    sortColumn: 'sort_order',
                    reindexOnReorder: false,
                },
            ),
        );

        act(() => {
            result.current.handleReorder(3, 1);
        });

        expect(result.current.items.map((item) => item.id)).toEqual([3, 1, 2]);
        expect(result.current.items.map((item) => item.sort_order)).toEqual([
            300, 100, 200,
        ]);
    });

    it('applies local optimistic reorder when endpoint is empty', () => {
        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);

        const { result } = renderHook(() =>
            useSortable(
                [
                    { id: 1, sort_order: 1, name: 'A' },
                    { id: 2, sort_order: 2, name: 'B' },
                ],
                {
                    endpoint: () => '   ',
                    sortColumn: 'sort_order',
                },
            ),
        );

        act(() => {
            result.current.handleReorder(2, 1);
        });

        expect(result.current.items.map((item) => item.id)).toEqual([2, 1]);
        expect(fetchMock).not.toHaveBeenCalled();
        expect(result.current.isReordering).toBe(false);
    });
});
