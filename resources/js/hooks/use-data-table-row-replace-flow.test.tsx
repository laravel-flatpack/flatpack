import { act, renderHook, waitFor } from '@testing-library/react';
import * as React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { useDataTableRowReplaceFlow } from '@/hooks/use-data-table-row-replace-flow';

function useRowReplaceHarness({
    onRowUpdate,
    onValueChange,
}: {
    onRowUpdate?: (payload: {
        rowId: string;
        row: Record<string, unknown>;
    }) => Promise<void> | void;
    onValueChange?: (value: unknown) => void;
}) {
    const [data, setData] = React.useState<Record<string, unknown>[]>([
        { id: '1', name: 'Old' },
    ]);
    const clearCreateDraftRowRef = React.useRef(vi.fn());
    const flow = useDataTableRowReplaceFlow({
        rowIdentity: {
            getStableRowId: (row) => String(row.id ?? ''),
            newRowIdPrefix: '__new__',
        },
        mutations: {
            setData,
            onValueChange,
            onRowUpdate,
        },
        clearCreateDraftRow: clearCreateDraftRowRef.current,
    });

    return {
        ...flow,
        data,
        clearCreateDraftRow: clearCreateDraftRowRef.current,
    };
}

describe('useDataTableRowReplaceFlow', () => {
    it('replaces existing row and notifies parent', async () => {
        const onValueChange = vi.fn();
        const { result } = renderHook(() =>
            useRowReplaceHarness({
                onValueChange,
            }),
        );

        act(() => {
            result.current.handleRowReplace('1', { id: '1', name: 'Updated' });
        });
        await Promise.resolve();

        expect(result.current.data).toEqual([{ id: '1', name: 'Updated' }]);
        expect(onValueChange).toHaveBeenCalledWith([
            { id: '1', name: 'Updated' },
        ]);
        expect(result.current.clearCreateDraftRow).not.toHaveBeenCalled();
    });

    it('appends when row id uses new prefix and clears create draft', async () => {
        const onValueChange = vi.fn();
        const { result } = renderHook(() =>
            useRowReplaceHarness({
                onValueChange,
            }),
        );

        act(() => {
            result.current.handleRowReplace('__new__:123', {
                id: '2',
                name: 'New row',
            });
        });
        await Promise.resolve();

        expect(result.current.data).toEqual([
            { id: '1', name: 'Old' },
            { id: '2', name: 'New row' },
        ]);
        expect(result.current.clearCreateDraftRow).toHaveBeenCalled();
        expect(onValueChange).toHaveBeenCalledWith([
            { id: '1', name: 'Old' },
            { id: '2', name: 'New row' },
        ]);
    });

    it('reverts optimistic replace when onRowUpdate fails', async () => {
        const onValueChange = vi.fn();
        const onRowUpdate = vi.fn(async () => {
            throw new Error('failed');
        });
        const { result } = renderHook(() =>
            useRowReplaceHarness({
                onValueChange,
                onRowUpdate,
            }),
        );

        act(() => {
            result.current.handleRowReplace('1', { id: '1', name: 'Changed' });
        });
        await waitFor(() => {
            expect(result.current.data).toEqual([{ id: '1', name: 'Old' }]);
        });
        expect(onRowUpdate).toHaveBeenCalledWith({
            rowId: '1',
            row: { id: '1', name: 'Changed' },
        });
        expect(onValueChange).toHaveBeenNthCalledWith(1, [
            { id: '1', name: 'Changed' },
        ]);
        expect(onValueChange).toHaveBeenNthCalledWith(2, [
            { id: '1', name: 'Old' },
        ]);
    });

    it('removes optimistic appended row when onRowUpdate fails', async () => {
        const onValueChange = vi.fn();
        const onRowUpdate = vi.fn(async () => {
            throw new Error('failed');
        });
        const { result } = renderHook(() =>
            useRowReplaceHarness({
                onValueChange,
                onRowUpdate,
            }),
        );

        act(() => {
            result.current.handleRowReplace('__new__:123', {
                id: '2',
                name: 'New row',
            });
        });
        await waitFor(() => {
            expect(result.current.data).toEqual([{ id: '1', name: 'Old' }]);
        });
        expect(onRowUpdate).toHaveBeenCalledWith({
            rowId: '__new__:123',
            row: { id: '2', name: 'New row' },
        });
        expect(onValueChange).toHaveBeenNthCalledWith(1, [
            { id: '1', name: 'Old' },
            { id: '2', name: 'New row' },
        ]);
        expect(onValueChange).toHaveBeenNthCalledWith(2, [
            { id: '1', name: 'Old' },
        ]);
    });
});
