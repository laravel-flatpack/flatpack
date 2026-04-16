import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { FlatpackListHeader } from '@/components/flatpack-list/flatpack-list-header';
import { DataTable } from '@/components/table/data-table';
import { useFlatpackList } from '@/hooks/use-flatpack-list';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

export default function FlatpackListPage(props: FlatpackListPageProps) {
    const {
        displayName,
        pageTitle,
        noContentMessage,
        columns,
        filterDefinitions,
        reorderable,
        rowClickEditKey,
        pendingListConfirm,
        setPendingListConfirm,
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
                open={pendingListConfirm !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingListConfirm(null);
                    }
                }}
                title={pendingListConfirm?.label ?? 'Confirm'}
                continueVariant={
                    pendingListConfirm?.variant === 'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    const pending = pendingListConfirm;
                    setPendingListConfirm(null);
                    if (pending !== null) {
                        void executeListAction(pending);
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
