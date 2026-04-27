import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { FlatpackPageHeader } from '@/components/flatpack/flatpack-page-header';
import { FlatpackListActions } from '@/components/flatpack-list/flatpack-list-actions';
import type { FlatpackMenuIconName } from '@/components/lucide-menu-icon-registry';
import { flatpackMenuIcons } from '@/components/lucide-menu-icon-registry';
import { DataTable } from '@/components/table/data-table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import { useFlatpackList } from '@/hooks/use-flatpack-list';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackListPageProps } from '@/types/pages/flatpack';

const NoColumnsMessage = ({ entity }: { entity: string }) => (
    <p className="text-sm text-muted-foreground">
        Define columns or tabs in{' '}
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
        allColumns,
        listTabPanels,
        listActiveTabId,
        handleListTabChange,
        filterDefinitions,
        reorderable,
        isRowClickEditPage,
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

    const listTabsToolbar =
        listTabPanels.length > 0 ? (
            <Tabs
                value={listActiveTabId ?? listTabPanels[0].id}
                onValueChange={handleListTabChange}
                className="w-full"
            >
                <div className="w-full overflow-x-auto">
                    <TabsList
                        variant="line"
                        className="inline-flex w-max min-w-max flex-nowrap justify-start"
                    >
                        {listTabPanels.map((panel) => {
                            const Icon =
                                panel.icon != null &&
                                panel.icon in flatpackMenuIcons
                                    ? flatpackMenuIcons[
                                          panel.icon as FlatpackMenuIconName
                                      ]
                                    : null;
                            return (
                                <TabsTrigger
                                    key={panel.id}
                                    value={panel.id}
                                    className="flex-none"
                                >
                                    {Icon != null ? (
                                        <Icon
                                            data-icon="inline-start"
                                            className="size-4"
                                        />
                                    ) : null}
                                    {panel.label}
                                </TabsTrigger>
                            );
                        })}
                    </TabsList>
                </div>
            </Tabs>
        ) : undefined;

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
                            searchTerm={searchTerm}
                            serverFilterState={serverFilterValues}
                        />
                    }
                />
                <div className="flex flex-col gap-6">
                    {allColumns.length > 0 ? (
                        <DataTable
                            id={`flatpack-list-${entity || 'entity'}`}
                            dataRowKey={modelKey || 'id'}
                            toolbarStart={listTabsToolbar}
                            bulkActions={bulkActions}
                            reorderable={reorderable}
                            onRowClick={
                                isRowClickEditPage ? handleRowClick : undefined
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
