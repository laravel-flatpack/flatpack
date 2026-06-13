import * as React from 'react';
import { deferNotifyParentFormValues } from '@/lib/data-table-utils';
import type {
    UseDataTableCellUpdateFlowOptions,
    UseDataTableCellUpdateFlowResult,
} from '@/types/data-table';

export function useDataTableCellUpdateFlow({
    rowIdentity,
    mutations,
}: UseDataTableCellUpdateFlowOptions): UseDataTableCellUpdateFlowResult {
    const { getStableRowId } = rowIdentity;
    const { setData, onValueChange, onCellUpdate } = mutations;

    const handleCellChange = React.useCallback(
        (rowId: string, columnId: string, next: unknown) => {
            let previousRow: Record<string, unknown> | null = null;
            let nextRow: Record<string, unknown> | null = null;
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => getStableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                const cur = prev[idx][columnId];
                if (Object.is(cur, next)) {
                    return prev;
                }
                previousRow = prev[idx];
                nextRow = { ...prev[idx], [columnId]: next };
                const resolvedNextRow = nextRow;
                const nextRows = prev.map((row, index) =>
                    index === idx ? resolvedNextRow : row,
                );
                deferNotifyParentFormValues(onValueChange, nextRows);
                return nextRows;
            });
            if (
                nextRow == null ||
                previousRow == null ||
                onCellUpdate == null
            ) {
                return;
            }
            void Promise.resolve(
                onCellUpdate({
                    rowId,
                    row: nextRow,
                    columnId,
                    value: next,
                }),
            ).catch(() => {
                setData((prev) => {
                    const idx = prev.findIndex(
                        (row, index) => getStableRowId(row, index) === rowId,
                    );
                    if (idx === -1) {
                        return prev;
                    }
                    const resolvedPreviousRow = previousRow;
                    if (resolvedPreviousRow == null) {
                        return prev;
                    }
                    const reverted = prev.map((row, index) =>
                        index === idx ? resolvedPreviousRow : row,
                    );
                    deferNotifyParentFormValues(onValueChange, reverted);
                    return reverted;
                });
            });
        },
        [getStableRowId, onCellUpdate, onValueChange, setData],
    );

    return { handleCellChange };
}
