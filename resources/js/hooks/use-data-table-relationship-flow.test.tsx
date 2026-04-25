import { act, renderHook } from '@testing-library/react';
import * as React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { useDataTableRelationshipFlow } from '@/hooks/use-data-table-relationship-flow';
import type { DataTableBulkDeletePayload } from '@/types/data-table';

const toastSuccess = vi.fn();

vi.mock('sonner', () => ({
    toast: {
        success: (...args: unknown[]) => toastSuccess(...args),
    },
}));

function useRelationshipHarness({
    onBulkAction,
    onRowAction,
    onValueChange,
}: {
    onBulkAction?: (
        payload: DataTableBulkDeletePayload,
    ) => Promise<void> | void;
    onRowAction?: (payload: {
        action: string;
        row: Record<string, unknown>;
        button?: { confirm?: boolean; success_message?: string };
    }) => Promise<void> | void;
    onValueChange?: (value: unknown) => void;
}) {
    const [data, setData] = React.useState<Record<string, unknown>[]>([
        { id: '1', name: 'Row 1' },
        { id: '2', name: 'Row 2' },
    ]);
    const [rowSelection, setRowSelection] = React.useState<
        Record<string, boolean>
    >({ '1': true });
    const [isAllRowsSelected, setIsAllRowsSelected] = React.useState(false);

    const flow = useDataTableRelationshipFlow({
        rowIdentity: {
            dataRowKey: 'id',
            getStableRowId: (row) => String(row.id ?? ''),
        },
        mutations: {
            data,
            setData,
            onValueChange,
        },
        onRowAction,
        onBulkAction,
        bulkActions: [
            { id: 'remove', label: 'Remove', action: 'remove' },
            { id: 'delete', label: 'Delete', action: 'delete' },
        ],
        rowSelection,
        setRowSelection,
        isAllRowsSelected,
        setIsAllRowsSelected,
        globalFilter: '',
        serverFilterState: {},
        serverSortingForBulkAction: { sort_by: null, sort_direction: null },
    });

    return {
        ...flow,
        data,
        rowSelection,
        isAllRowsSelected,
        setRowSelection,
        setIsAllRowsSelected,
    };
}

describe('useDataTableRelationshipFlow', () => {
    it('ignores unknown row actions without side effects', () => {
        const onValueChange = vi.fn();
        const { result } = renderHook(() =>
            useRelationshipHarness({
                onValueChange,
            }),
        );

        act(() => {
            result.current.handleRowAction({
                action: 'archive',
                row: { id: '1', name: 'Row 1' },
            });
        });

        expect(result.current.data).toHaveLength(2);
        expect(result.current.pendingEmbeddedRowConfirm).toBeNull();
        expect(onValueChange).not.toHaveBeenCalled();
    });

    it('opens and dismisses confirm state for confirmable row action', () => {
        const { result } = renderHook(() =>
            useRelationshipHarness({ onValueChange: vi.fn() }),
        );

        act(() => {
            result.current.handleRowAction({
                action: 'remove',
                row: { id: '1', name: 'Row 1' },
                button: { label: 'Remove', confirm: true },
            });
        });
        expect(result.current.pendingEmbeddedRowConfirm).not.toBeNull();

        act(() => {
            result.current.dismissPendingRowActionConfirm();
        });
        expect(result.current.pendingEmbeddedRowConfirm).toBeNull();
    });

    it('confirms pending row action and removes row with success toast', async () => {
        const onValueChange = vi.fn();
        const { result } = renderHook(() =>
            useRelationshipHarness({
                onValueChange,
            }),
        );

        act(() => {
            result.current.handleRowAction({
                action: 'remove',
                row: { id: '1', name: 'Row 1' },
                button: {
                    label: 'Remove',
                    confirm: true,
                    success_message: 'Removed.',
                },
            });
        });

        act(() => {
            result.current.confirmPendingRowAction();
        });

        await Promise.resolve();

        expect(result.current.pendingEmbeddedRowConfirm).toBeNull();
        expect(result.current.data).toEqual([{ id: '2', name: 'Row 2' }]);
        expect(onValueChange).toHaveBeenCalledWith([
            { id: '2', name: 'Row 2' },
        ]);
        expect(toastSuccess).toHaveBeenCalledWith('Removed.');
    });

    it('applies fallback bulk remove and calls deselect callback', async () => {
        const onValueChange = vi.fn();
        const deselect = vi.fn();
        const { result } = renderHook(() =>
            useRelationshipHarness({
                onValueChange,
            }),
        );

        await act(async () => {
            await result.current.handleBulkAction('remove', deselect);
        });
        await Promise.resolve();

        expect(deselect).toHaveBeenCalled();
        expect(result.current.data).toEqual([{ id: '2', name: 'Row 2' }]);
        expect(onValueChange).toHaveBeenCalledWith([
            { id: '2', name: 'Row 2' },
        ]);
    });

    it('passes selection all for server bulk delete when all rows selected', async () => {
        const onBulkAction = vi.fn(async () => undefined);
        const deselect = vi.fn();
        const { result } = renderHook(() =>
            useRelationshipHarness({
                onBulkAction,
            }),
        );

        act(() => {
            result.current.setIsAllRowsSelected(true);
            result.current.setRowSelection({ '1': true });
        });

        await act(async () => {
            await result.current.handleBulkAction('delete', deselect);
        });

        expect(onBulkAction).toHaveBeenCalledWith(
            expect.objectContaining({
                action: 'delete',
                selection: 'all',
            }),
        );
        expect(deselect).toHaveBeenCalled();
    });
});
