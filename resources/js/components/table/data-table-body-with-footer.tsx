import type { DragEndEvent } from '@dnd-kit/core';
import type { Table } from '@tanstack/react-table';
import * as React from 'react';
import { DataTableBody } from '@/components/table/data-table-body';
import { DataTableFooter } from '@/components/table/data-table-footer';
import type { DataTableRowValidationMessagesById } from '@/types/data-table';
import { DATA_TABLE_PAGE_SIZE_OPTIONS } from './data-table-constants';

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
    pagination?: boolean;
    onDragEnd: (event: DragEndEvent) => void;
    rowValidationMessagesById: DataTableRowValidationMessagesById;
    isReordering: boolean;
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
    pagination,
    onDragEnd,
    rowValidationMessagesById,
    isReordering,
}: DataTableBodyWithFooterProps): React.JSX.Element {
    const paginationStateCurrent = table.getState().pagination;
    const pageCount = table.getPageCount() || 1;
    const shouldShowPagination =
        pagination === true
            ? true
            : pagination === false
              ? false
              : pageCount > 1;

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
                {isReordering ? (
                    <div className="px-3 py-1 text-right text-xs text-muted-foreground">
                        Saving...
                    </div>
                ) : null}
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
            {shouldShowPagination ? (
                <DataTableFooter
                    id={id}
                    rowCountLabel={rowCountLabel}
                    pageSizeOptions={DATA_TABLE_PAGE_SIZE_OPTIONS}
                    pageSize={paginationStateCurrent.pageSize}
                    pageIndex={paginationStateCurrent.pageIndex}
                    pageCount={pageCount}
                    canPreviousPage={table.getCanPreviousPage()}
                    canNextPage={table.getCanNextPage()}
                    onPageSizeChange={(value) => {
                        table.setPageSize(Number(value));
                    }}
                    onFirstPage={() => table.setPageIndex(0)}
                    onPreviousPage={() => table.previousPage()}
                    onNextPage={() => table.nextPage()}
                    onLastPage={() => table.setPageIndex(pageCount - 1)}
                />
            ) : null}
        </>
    );
}
