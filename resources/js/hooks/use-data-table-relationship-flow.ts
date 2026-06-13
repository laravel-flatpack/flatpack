import * as React from 'react';
import { toast } from 'sonner';
import { isDestructiveActionKey } from '@/lib/data-table-action-semantics';
import { deferNotifyParentFormValues } from '@/lib/data-table-utils';
import type {
    DataTableRowActionPayload,
    UseDataTableRelationshipFlowOptions,
    UseDataTableRelationshipFlowResult,
} from '@/types/data-table';

export function useDataTableRelationshipFlow({
    rowIdentity,
    mutations,
    onRowAction,
    onBulkAction,
    bulkActions,
    rowSelection,
    setRowSelection,
    isAllRowsSelected,
    setIsAllRowsSelected,
    globalFilter,
    serverFilterState,
    serverSortingForBulkAction,
}: UseDataTableRelationshipFlowOptions): UseDataTableRelationshipFlowResult {
    const { dataRowKey, getStableRowId } = rowIdentity;
    const { data, setData, onValueChange } = mutations;
    const resolveEmbeddedRowRemoval = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            setData((prev) => {
                const keyVal = payload.row[dataRowKey];
                let targetStable: string | null = null;
                if (keyVal != null && keyVal !== '') {
                    targetStable = String(keyVal);
                } else {
                    const idx = prev.indexOf(payload.row);
                    if (idx !== -1) {
                        targetStable = getStableRowId(prev[idx], idx);
                    }
                }
                if (targetStable === null) {
                    return prev;
                }
                const nextRows = prev.filter(
                    (row, index) => getStableRowId(row, index) !== targetStable,
                );
                deferNotifyParentFormValues(onValueChange, nextRows);
                return nextRows;
            });
        },
        [dataRowKey, getStableRowId, onValueChange, setData],
    );

    const [pendingEmbeddedRowConfirm, setPendingEmbeddedRowConfirm] =
        React.useState<DataTableRowActionPayload | null>(null);

    const applyEmbeddedDestructiveRowAction = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            const action = payload.action.toLowerCase();
            if (!isDestructiveActionKey(action)) {
                return;
            }
            resolveEmbeddedRowRemoval(payload);
            const message = payload.button?.success_message;
            if (typeof message === 'string' && message.trim() !== '') {
                toast.success(message.trim());
            }
        },
        [resolveEmbeddedRowRemoval],
    );

    const handleRowAction = React.useCallback(
        (payload: DataTableRowActionPayload) => {
            if (onRowAction != null) {
                void Promise.resolve(onRowAction(payload));
                return;
            }
            if (onValueChange == null) {
                return;
            }
            if (!isDestructiveActionKey(payload.action)) {
                return;
            }
            if (payload.button?.confirm === true) {
                setPendingEmbeddedRowConfirm(payload);
                return;
            }
            applyEmbeddedDestructiveRowAction(payload);
        },
        [applyEmbeddedDestructiveRowAction, onRowAction, onValueChange],
    );

    const handleBulkAction = React.useCallback(
        async (actionId: string, handleDeselectAllRows: () => void) => {
            const selectedIds = new Set(
                Object.entries(rowSelection)
                    .filter(([, selected]) => selected)
                    .map(([rowId]) => rowId),
            );

            if (selectedIds.size === 0) {
                return;
            }

            const bulkConfig = bulkActions.find(
                (action) => action.id === actionId,
            );
            const actionKey = bulkConfig?.action?.trim();
            if (!actionKey) {
                return;
            }

            if (onBulkAction != null) {
                const previousData = data;
                const previousSelection = rowSelection;
                const previousIsAllRowsSelected = isAllRowsSelected;
                const optimisticRows =
                    actionKey === 'delete' && isAllRowsSelected
                        ? []
                        : actionKey === 'delete'
                          ? data.filter(
                                (row, index) =>
                                    !selectedIds.has(
                                        getStableRowId(row, index),
                                    ),
                            )
                          : data;

                if (actionKey === 'delete') {
                    setData(optimisticRows);
                    deferNotifyParentFormValues(onValueChange, optimisticRows);
                }
                handleDeselectAllRows();

                try {
                    await onBulkAction({
                        action: actionKey,
                        selection: isAllRowsSelected
                            ? 'all'
                            : Array.from(selectedIds),
                        search: globalFilter,
                        filters: serverFilterState,
                        sorting: serverSortingForBulkAction,
                    });
                    return;
                } catch (error) {
                    if (actionKey === 'delete') {
                        setData(previousData);
                    }
                    setRowSelection(previousSelection);
                    setIsAllRowsSelected(previousIsAllRowsSelected);
                    throw error;
                }
            }

            if (isDestructiveActionKey(actionKey)) {
                setData((prev) => {
                    const nextRows = prev.filter(
                        (row, index) =>
                            !selectedIds.has(getStableRowId(row, index)),
                    );
                    deferNotifyParentFormValues(onValueChange, nextRows);
                    return nextRows;
                });
                const message = bulkConfig?.success_message;
                if (typeof message === 'string' && message.trim() !== '') {
                    queueMicrotask(() => {
                        toast.success(message.trim());
                    });
                }
            }

            handleDeselectAllRows();
        },
        [
            bulkActions,
            data,
            getStableRowId,
            globalFilter,
            isAllRowsSelected,
            onBulkAction,
            onValueChange,
            rowSelection,
            serverFilterState,
            serverSortingForBulkAction,
            setData,
            setIsAllRowsSelected,
            setRowSelection,
        ],
    );

    const clearPendingEmbeddedRowConfirm = React.useCallback(() => {
        setPendingEmbeddedRowConfirm(null);
    }, []);

    const confirmPendingRowAction = React.useCallback(() => {
        const payload = pendingEmbeddedRowConfirm;
        setPendingEmbeddedRowConfirm(null);
        if (payload !== null) {
            applyEmbeddedDestructiveRowAction(payload);
        }
    }, [applyEmbeddedDestructiveRowAction, pendingEmbeddedRowConfirm]);

    return {
        handleRowAction,
        handleBulkAction,
        pendingEmbeddedRowConfirm,
        dismissPendingRowActionConfirm: clearPendingEmbeddedRowConfirm,
        confirmPendingRowAction,
    };
}
