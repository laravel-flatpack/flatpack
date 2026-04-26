import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { FlatpackPageHeader } from '@/components/flatpack/flatpack-page-header';
import { FlatpackListActions } from '@/components/flatpack-list/flatpack-list-actions';
import { DataTable } from '@/components/table/data-table';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import { useFlatpackList } from '@/hooks/use-flatpack-list';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

const NoColumnsMessage = ({ entity }: { entity: string }) => (
    <p className="text-sm text-muted-foreground">
        Define columns in{' '}
        <code className="font-mono text-xs bg-muted px-1 py-0.5 rounded-md">{`/${entity}/list.yaml`}</code>{' '}
        to render this table.
    </p>
);

export default function FlatpackListPage(props: FlatpackListPageProps) {
    useCompositionDebugLog(props.composition_debug);
    const {
        displayName,
        pageTitle,
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
            <Head title={pageTitle} />
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
                <FlatpackPageHeader
                    title={displayName}
                    actions={
                        <FlatpackListActions
                            listActions={listActions}
                            onRequestConfirm={setPendingListConfirm}
                            runListAction={executeListAction}
                        />
                    }
                />
                <div className="flex flex-col gap-6">
                    {columns.length > 0 ? (
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
                    ) : (
                        <NoColumnsMessage entity={entity} />
                    )}
                </div>
            </div>
        </>
    );
}

FlatpackListPage.layout = (page: ReactElement) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
