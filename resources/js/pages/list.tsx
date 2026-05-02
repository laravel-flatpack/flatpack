import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { ConfirmDialog } from '@/components/actions/confirm-dialog';
import type { MenuIconName } from '@/components/icons/lucide-menu-icon-registry';
import { menuIcons } from '@/components/icons/lucide-menu-icon-registry';
import { ListActions } from '@/components/shell/list/list-actions';
import { PageHeader } from '@/components/shell/page-header';
import { DataTable } from '@/components/table/data-table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { WidgetsRenderer } from '@/components/widgets/widgets-renderer';
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
        isRowClickEditDrawer,
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
        reorderEndpoint,
        handleReorderError,
        records,
        pagination,
        paginationVisibility,
        showColumnsVisibility,
        searchTerm,
        serverFilterValues,
        serverSorting,
        bulkActions,
        listActions,
        entity,
        modelKey,
        widgets,
        widgetsSchema,
    } = useFlatpackList(props);

    const hasListWidgets =
        widgets != null &&
        typeof widgets === 'object' &&
        Object.keys(widgets).length > 0;

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
                                panel.icon != null && panel.icon in menuIcons
                                    ? menuIcons[panel.icon as MenuIconName]
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
            <ConfirmDialog
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
                <PageHeader
                    title={displayName}
                    actions={
                        <ListActions
                            listActions={listActions}
                            onRequestConfirm={setPendingListConfirm}
                            runListAction={executeListAction}
                            searchTerm={searchTerm}
                            serverFilterState={serverFilterValues}
                        />
                    }
                />
                {hasListWidgets ? (
                    <WidgetsRenderer
                        widgets={widgets}
                        tabPanels={widgetsSchema?.tab_panels}
                        className="mb-4"
                    />
                ) : null}
                <div className="flex flex-col gap-6">
                    {allColumns.length > 0 ? (
                        <DataTable
                            id={`flatpack-list-${entity || 'entity'}`}
                            dataRowKey={modelKey || 'id'}
                            toolbarStart={listTabsToolbar}
                            bulkActions={bulkActions}
                            reorderable={reorderable}
                            rowDetailDrawer={isRowClickEditDrawer}
                            openDetailDrawerOnRowClick={isRowClickEditDrawer}
                            onRowClick={
                                isRowClickEditPage ? handleRowClick : undefined
                            }
                            columns={columns}
                            data={records}
                            serverPagination={pagination}
                            pagination={paginationVisibility}
                            showColumnsVisibility={showColumnsVisibility}
                            serverSearch={searchTerm}
                            serverFilters={filterDefinitions}
                            serverFilterValues={serverFilterValues}
                            serverSorting={serverSorting}
                            onBulkAction={handleBulkAction}
                            onRowAction={handleRowAction}
                            onCellUpdate={handleCellUpdate}
                            onRowUpdate={handleRowUpdate}
                            reorderEndpoint={reorderEndpoint}
                            reorderOnError={handleReorderError}
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
