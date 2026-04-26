import type { DragEndEvent } from '@dnd-kit/core';
import type { Table } from '@tanstack/react-table';
import * as React from 'react';
import { DataTableBody } from '@/components/table/data-table-body';
import { DataTableFooter } from '@/components/table/data-table-footer';
import type { DataTableRowValidationMessagesById } from '@/types/data-table';

const LazyDataTableDndWrapper = React.lazy(() =>
    import('@/components/table/data-table-dnd-wrapper').then((module) => ({
        default: module.DataTableDndWrapper,
    })),
);

type DataTableBodyWithFooterProps = {
    id: string;
    table: Table<Record<string, unknown>>;
    isReorderable: boolean;
    onRowClick?: (
        event: React.MouseEvent<HTMLTableRowElement>,
        row: Record<string, unknown>,
    ) => void;
    emptyColSpan: number;
    rowCountLabel: string;
    onDragEnd: (event: DragEndEvent) => void;
    rowValidationMessagesById: DataTableRowValidationMessagesById;
};

/**
 * Presentational table rendering layer extracted from controller orchestration.
 */
export function DataTableBodyWithFooter({
    id,
    table,
    isReorderable,
    onRowClick,
    emptyColSpan,
    rowCountLabel,
    onDragEnd,
    rowValidationMessagesById,
}: DataTableBodyWithFooterProps): React.JSX.Element {
    const paginationStateCurrent = table.getState().pagination;

    const tableBody = (
        <DataTableBody
            table={table}
            isReorderable={isReorderable}
            onRowClick={onRowClick}
            emptyColSpan={emptyColSpan}
            rowValidationMessagesById={rowValidationMessagesById}
        />
    );

    return (
        <>
            <div className="overflow-hidden rounded-lg border">
                {isReorderable ? (
                    <React.Suspense fallback={tableBody}>
                        <LazyDataTableDndWrapper onDragEnd={onDragEnd}>
                            {tableBody}
                        </LazyDataTableDndWrapper>
                    </React.Suspense>
                ) : (
                    tableBody
                )}
            </div>
            <DataTableFooter
                id={id}
                rowCountLabel={rowCountLabel}
                pageSize={paginationStateCurrent.pageSize}
                pageIndex={paginationStateCurrent.pageIndex}
                pageCount={table.getPageCount()}
                canPreviousPage={table.getCanPreviousPage()}
                canNextPage={table.getCanNextPage()}
                onPageSizeChange={(value) => {
                    table.setPageSize(Number(value));
                }}
                onFirstPage={() => table.setPageIndex(0)}
                onPreviousPage={() => table.previousPage()}
                onNextPage={() => table.nextPage()}
                onLastPage={() => table.setPageIndex(table.getPageCount() - 1)}
            />
        </>
    );
}
