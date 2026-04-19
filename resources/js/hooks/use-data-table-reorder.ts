import type { DragEndEvent } from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import type { Table as TanStackTable } from '@tanstack/react-table';
import * as React from 'react';
import {
    deferNotifyParentFormValues,
    reindexReorderColumn,
    stableRowId,
} from '@/lib/data-table-utils';

type UseDataTableReorderOptions = {
    data: Record<string, unknown>[];
    table: TanStackTable<Record<string, unknown>>;
    reorderKey: string | null;
    onValueChange?: (value: unknown) => void;
    onReorderApplied: (nextData: Record<string, unknown>[]) => void;
    onReorderCompleted?: () => void;
};

export function useDataTableReorder({
    data,
    table,
    reorderKey,
    onValueChange,
    onReorderApplied,
    onReorderCompleted,
}: UseDataTableReorderOptions) {
    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            if (!reorderKey) {
                return;
            }
            const { active, over } = event;
            if (!over || active.id === over.id) {
                return;
            }
            const pageRows = table.getRowModel().rows;
            const pageRowIds = pageRows.map((row) => row.id);
            const oldPageIdx = pageRowIds.indexOf(String(active.id));
            const newPageIdx = pageRowIds.indexOf(String(over.id));
            if (oldPageIdx === -1 || newPageIdx === -1) {
                return;
            }
            const fullIndices = pageRowIds.map((rowId) =>
                data.findIndex((row, idx) => stableRowId(row, idx) === rowId),
            );
            if (fullIndices.some((index) => index < 0)) {
                return;
            }
            const pageSlice: Record<string, unknown>[] = [];
            for (const index of fullIndices) {
                const row = data[index];
                if (row === undefined) {
                    return;
                }
                pageSlice.push(row);
            }
            const reorderedPage = arrayMove(pageSlice, oldPageIdx, newPageIdx);
            const next = [...data];
            for (
                let pageIndex = 0;
                pageIndex < fullIndices.length;
                pageIndex++
            ) {
                const moved = reorderedPage[pageIndex];
                if (moved === undefined) {
                    return;
                }
                next[fullIndices[pageIndex]] = moved;
            }
            const withOrder = reindexReorderColumn(next, reorderKey);
            onReorderApplied(withOrder);
            onReorderCompleted?.();
            deferNotifyParentFormValues(onValueChange, withOrder);
        },
        [
            data,
            onReorderApplied,
            onReorderCompleted,
            onValueChange,
            reorderKey,
            table,
        ],
    );

    return { handleDragEnd };
}
