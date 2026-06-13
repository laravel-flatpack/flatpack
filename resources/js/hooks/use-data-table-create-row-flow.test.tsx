import { act, renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useDataTableCreateRowFlow } from '@/hooks/use-data-table-create-row-flow';
import type { FlatpackDataTableColumn } from '@/types/data-table';

describe('useDataTableCreateRowFlow', () => {
    const columns: FlatpackDataTableColumn[] = [
        { id: 'name', label: 'Name', type: 'text' },
        { id: 'enabled', label: 'Enabled', type: 'text' },
    ];

    it('starts create flow from toolbar action key, not id', () => {
        vi.spyOn(Date, 'now').mockReturnValue(1700000000000);
        const onToolbarAction = vi.fn();
        const { result } = renderHook(() =>
            useDataTableCreateRowFlow({
                rowDetailDrawer: true,
                toolbarActions: [
                    { id: 'new-line', label: 'New', action: 'create' },
                ],
                schemaColumns: columns,
                rowIdentity: {
                    getStableRowId: (row) => String(row.id ?? ''),
                },
                mutations: {
                    data: [],
                    onToolbarAction,
                },
            }),
        );

        act(() => {
            result.current.handleToolbarActionClick('new-line');
        });

        expect(result.current.detailDrawerOpen).toBe(true);
        expect(result.current.detailDrawerRowId).toBe('__new__:1700000000000');
        expect(result.current.detailDrawerBodyVariant).toBe('rowFields');
        expect(result.current.detailDrawerRow).toEqual({
            name: '',
            enabled: '',
        });
        expect(onToolbarAction).not.toHaveBeenCalled();
    });

    it('delegates create toolbar to parent when skipEmbeddedTableCreateDraft is true', () => {
        vi.spyOn(Date, 'now').mockReturnValue(1700000000000);
        const onToolbarAction = vi.fn();
        const { result } = renderHook(() =>
            useDataTableCreateRowFlow({
                rowDetailDrawer: true,
                skipEmbeddedTableCreateDraft: true,
                toolbarActions: [
                    { id: 'create', label: 'Create', action: 'create' },
                ],
                schemaColumns: columns,
                rowIdentity: {
                    getStableRowId: (row) => String(row.id ?? ''),
                },
                mutations: {
                    data: [],
                    onToolbarAction,
                },
            }),
        );

        act(() => {
            result.current.handleToolbarActionClick('create');
        });

        expect(onToolbarAction).toHaveBeenCalledWith('create');
        expect(result.current.detailDrawerOpen).toBe(false);
    });

    it('delegates unknown toolbar actions to parent handler', () => {
        const onToolbarAction = vi.fn();
        const { result } = renderHook(() =>
            useDataTableCreateRowFlow({
                rowDetailDrawer: true,
                toolbarActions: [
                    { id: 'export', label: 'Export', action: 'export' },
                ],
                schemaColumns: columns,
                rowIdentity: {
                    getStableRowId: (row) => String(row.id ?? ''),
                },
                mutations: {
                    data: [],
                    onToolbarAction,
                },
            }),
        );

        act(() => {
            result.current.handleToolbarActionClick('export');
        });

        expect(onToolbarAction).toHaveBeenCalledWith('export');
        expect(result.current.detailDrawerOpen).toBe(false);
    });

    it('resets draft and row id when drawer closes', () => {
        vi.spyOn(Date, 'now').mockReturnValue(1700000000001);
        const { result } = renderHook(() =>
            useDataTableCreateRowFlow({
                rowDetailDrawer: true,
                toolbarActions: [
                    { id: 'create', label: 'Create', action: 'create' },
                ],
                schemaColumns: columns,
                rowIdentity: {
                    getStableRowId: (row) => String(row.id ?? ''),
                },
                mutations: {
                    data: [],
                },
            }),
        );

        act(() => {
            result.current.handleToolbarActionClick('create');
        });
        expect(result.current.detailDrawerOpen).toBe(true);
        expect(result.current.detailDrawerRowId).not.toBeNull();

        act(() => {
            result.current.handleDetailDrawerOpenChange(false);
        });

        expect(result.current.detailDrawerOpen).toBe(false);
        expect(result.current.detailDrawerRowId).toBeNull();
        expect(result.current.detailDrawerRow).toBeNull();
    });

    it('opens draft drawer for add toolbar action like create', () => {
        vi.spyOn(Date, 'now').mockReturnValue(1700000000002);
        const onToolbarAction = vi.fn();
        const { result } = renderHook(() =>
            useDataTableCreateRowFlow({
                rowDetailDrawer: true,
                toolbarActions: [
                    { id: 'add-existing', label: 'Add', action: 'add' },
                ],
                schemaColumns: columns,
                rowIdentity: {
                    getStableRowId: (row) => String(row.id ?? ''),
                },
                mutations: {
                    data: [],
                    onToolbarAction,
                },
            }),
        );

        act(() => {
            result.current.handleToolbarActionClick('add-existing');
        });

        expect(result.current.detailDrawerOpen).toBe(true);
        expect(result.current.detailDrawerRowId).toBe('__new__:1700000000002');
        expect(result.current.detailDrawerBodyVariant).toBe('rowFields');
        expect(onToolbarAction).not.toHaveBeenCalled();
    });

    it('sets attach body variant for toolbar action attach (BelongsToMany)', () => {
        vi.spyOn(Date, 'now').mockReturnValue(1700000000003);
        const onToolbarAction = vi.fn();
        const { result } = renderHook(() =>
            useDataTableCreateRowFlow({
                rowDetailDrawer: true,
                toolbarActions: [
                    { id: 'att', label: 'Attach', action: 'attach' },
                ],
                schemaColumns: columns,
                rowIdentity: {
                    getStableRowId: (row) => String(row.id ?? ''),
                },
                mutations: {
                    data: [],
                    onToolbarAction,
                },
            }),
        );

        act(() => {
            result.current.handleToolbarActionClick('att');
        });

        expect(result.current.detailDrawerOpen).toBe(true);
        expect(result.current.detailDrawerRowId).toBe('__new__:1700000000003');
        expect(result.current.detailDrawerBodyVariant).toBe('attachExisting');
        expect(result.current.detailDrawerRow).toEqual({
            name: '',
            enabled: '',
        });
        expect(onToolbarAction).not.toHaveBeenCalled();
    });

    it('auto-closes when open row no longer exists', () => {
        const { result, rerender } = renderHook(
            ({ data }: { data: Record<string, unknown>[] }) =>
                useDataTableCreateRowFlow({
                    rowDetailDrawer: true,
                    toolbarActions: [],
                    schemaColumns: columns,
                    rowIdentity: {
                        getStableRowId: (row) => String(row.id ?? ''),
                    },
                    mutations: {
                        data,
                    },
                }),
            {
                initialProps: {
                    data: [{ id: 1, name: 'Row 1', enabled: true }],
                },
            },
        );

        act(() => {
            result.current.openDetailDrawerForRow('1');
        });
        expect(result.current.detailDrawerOpen).toBe(true);
        expect(result.current.detailDrawerRow).toEqual({
            id: 1,
            name: 'Row 1',
            enabled: true,
        });

        rerender({ data: [] });

        expect(result.current.detailDrawerOpen).toBe(false);
        expect(result.current.detailDrawerRowId).toBeNull();
        expect(result.current.detailDrawerRow).toBeNull();
    });
});
