import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { FlatpackListHeader } from '@/components/flatpack-list/flatpack-list-header';
import { DataTable } from '@/components/table/data-table';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import { useFlatpackList } from '@/hooks/use-flatpack-list';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage(props: FlatpackListPageProps) {
    useCompositionDebugLog(props.composition_debug);
    const {
        displayName,
        pageTitle,
        noContentMessage,
        columns,
        filterDefinitions,
        reorderable,
        rowClickEditKey,
        pendingListConfirm,
        pendingRowActionConfirm,
        setPendingListConfirm,
        setPendingRowActionConfirm,
        executeRowAction,
        handleRowClick,
        handleServerPaginationChange,
        handleBulkAction,
        handleRowAction,
        executeListAction,
        handleCellUpdate,
        handleRowUpdate,
        records,
        pagination,
        searchTerm,
        serverFilterValues,
        serverSorting,
        bulkActions,
        listActions,
        entity,
        modelKey,
    } = useFlatpackList(props);

    return (
        <>
            {displayName ? <Head title={pageTitle} /> : null}
            <FlatpackConfirmDialog
                open={
                    pendingListConfirm !== null ||
                    pendingRowActionConfirm !== null
                }
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingListConfirm(null);
                        setPendingRowActionConfirm(null);
                    }
                }}
                title={
                    pendingListConfirm?.label ??
                    pendingRowActionConfirm?.label ??
                    'Confirm'
                }
                continueVariant={
                    pendingListConfirm?.variant === 'destructive' ||
                    pendingRowActionConfirm?.variant === 'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    const listPending = pendingListConfirm;
                    const rowPending = pendingRowActionConfirm;
                    setPendingListConfirm(null);
                    setPendingRowActionConfirm(null);
                    if (listPending !== null) {
                        void executeListAction(listPending);
                    } else if (rowPending !== null) {
                        void executeRowAction({
                            action: rowPending.action,
                            row: rowPending.row,
                            success_message: rowPending.success_message,
                        });
                    }
                }}
            />
            <div className="flex flex-col gap-2">
                {displayName ? (
                    <FlatpackListHeader
                        displayName={displayName}
                        listActions={listActions}
                        onRequestConfirm={setPendingListConfirm}
                        runListAction={executeListAction}
                    />
                ) : null}
                {noContentMessage ? (
                    <p className="text-muted-foreground">{noContentMessage}</p>
                ) : (
                    <DataTable
                        id={`flatpack-list-${entity || 'entity'}`}
                        dataRowKey={modelKey || 'id'}
                        bulkActions={bulkActions}
                        reorderable={reorderable}
                        onRowClick={
                            rowClickEditKey !== null
                                ? handleRowClick
                                : undefined
                        }
                        columns={columns}
                        data={records}
                        serverPagination={pagination}
                        serverSearch={searchTerm}
                        serverFilters={filterDefinitions}
                        serverFilterValues={serverFilterValues}
                        serverSorting={serverSorting}
                        onBulkAction={handleBulkAction}
                        onRowAction={handleRowAction}
                        onCellUpdate={handleCellUpdate}
                        onRowUpdate={handleRowUpdate}
                        onServerPaginationChange={
                            pagination
                                ? handleServerPaginationChange
                                : undefined
                        }
                    />
                )}
            </div>
        </>
    );
}

FlatpackListPage.layout = (page: ReactElement) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
