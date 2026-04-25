import { act, renderHook, waitFor } from '@testing-library/react';
import * as React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { useDataTableCellUpdateFlow } from '@/hooks/use-data-table-cell-update-flow';

function useCellUpdateHarness({
    onCellUpdate,
    onValueChange,
}: {
    onCellUpdate?: (payload: {
        rowId: string;
        row: Record<string, unknown>;
        columnId: string;
        value: unknown;
    }) => Promise<void> | void;
    onValueChange?: (value: unknown) => void;
}) {
    const [data, setData] = React.useState<Record<string, unknown>[]>([
        { id: '1', name: 'Old' },
    ]);
    const flow = useDataTableCellUpdateFlow({
        rowIdentity: {
            getStableRowId: (row) => String(row.id ?? ''),
        },
        mutations: {
            setData,
            onValueChange,
            onCellUpdate,
        },
    });

    return { ...flow, data };
}

describe('useDataTableCellUpdateFlow', () => {
    it('updates cell optimistically and notifies parent', async () => {
        const onValueChange = vi.fn();
        const onCellUpdate = vi.fn(async () => undefined);
        const { result } = renderHook(() =>
            useCellUpdateHarness({
                onCellUpdate,
                onValueChange,
            }),
        );

        act(() => {
            result.current.handleCellChange('1', 'name', 'Updated');
        });
        await Promise.resolve();

        expect(result.current.data).toEqual([{ id: '1', name: 'Updated' }]);
        expect(onValueChange).toHaveBeenCalledWith([
            { id: '1', name: 'Updated' },
        ]);
        expect(onCellUpdate).toHaveBeenCalledWith({
            rowId: '1',
            row: { id: '1', name: 'Updated' },
            columnId: 'name',
            value: 'Updated',
        });
    });

    it('reverts on cell update failure', async () => {
        const onValueChange = vi.fn();
        const onCellUpdate = vi.fn(async () => {
            throw new Error('fail');
        });
        const { result } = renderHook(() =>
            useCellUpdateHarness({
                onCellUpdate,
                onValueChange,
            }),
        );

        act(() => {
            result.current.handleCellChange('1', 'name', 'Changed');
        });

        await waitFor(() => {
            expect(result.current.data).toEqual([{ id: '1', name: 'Old' }]);
        });
        expect(onValueChange).toHaveBeenNthCalledWith(1, [
            { id: '1', name: 'Changed' },
        ]);
        expect(onValueChange).toHaveBeenNthCalledWith(2, [
            { id: '1', name: 'Old' },
        ]);
    });
});
