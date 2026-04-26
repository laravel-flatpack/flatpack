import type { Table as TanStackTable } from '@tanstack/react-table';
import { ChevronDownIcon } from 'lucide-react';
import { useState } from 'react';
import {
    flatpackActionEnabledState,
    flatpackActionVisibilityState,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { FlatpackConfirmDialog } from '@/components/flatpack/flatpack-confirm-dialog';
import { LucideIconByName } from '@/components/icons';
import { DataTableColumnsVisibilityDropdown } from '@/components/table/data-table-columns-visibility-dropdown';
import { DataTableFiltersDropdown } from '@/components/table/data-table-filters-dropdown';
import { DataTableSearchInput } from '@/components/table/data-table-search-input';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type {
    FlatpackDataTableBulkAction,
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
    FlatpackFormTableToolbarAction,
} from '@/types/data-table';

type DataTableToolbarProps = {
    id: string;
    table: TanStackTable<Record<string, unknown>>;
    hasToolbarActions: boolean;
    toolbarActions: FlatpackFormTableToolbarAction[];
    onToolbarAction: (actionId: string) => void;
    toolbarActionsDisabled: boolean;
    /** Native tooltip when {@link toolbarActionsDisabled} is true. */
    toolbarActionsDisabledTitle?: string;
    hasBulkActions: boolean;
    selectedRowCount: number;
    isAllRowsSelected: boolean;
    totalRowCount: number;
    onSelectAllRows: () => void;
    onDeselectAllRows: () => void;
    bulkActions: FlatpackDataTableBulkAction[];
    onBulkAction: (actionId: string) => void | Promise<void>;
    hasSearchableColumns: boolean;
    hasFilters: boolean;
    globalFilter: string;
    onGlobalFilterChange: (value: string) => void;
    serverFilters: FlatpackDataTableFilter[];
    serverFilterState: FlatpackDataTableServerFiltersState;
    onSetSingleFilter: (filterId: string, value: string) => void;
    onToggleMultiFilterValue: (filterId: string, optionValue: string) => void;
    onSetDateFilter: (filterId: string, date?: Date) => void;
};

export function DataTableToolbar({
    id,
    table,
    hasToolbarActions,
    toolbarActions,
    onToolbarAction,
    toolbarActionsDisabled,
    toolbarActionsDisabledTitle,
    hasBulkActions,
    selectedRowCount,
    isAllRowsSelected,
    totalRowCount,
    onSelectAllRows,
    onDeselectAllRows,
    bulkActions,
    onBulkAction,
    hasSearchableColumns,
    hasFilters,
    globalFilter,
    onGlobalFilterChange,
    serverFilters,
    serverFilterState,
    onSetSingleFilter,
    onToggleMultiFilterValue,
    onSetDateFilter,
}: DataTableToolbarProps) {
    const toolbarDisabledHint =
        toolbarActionsDisabledTitle ??
        'Save the parent record before using these actions.';
    const [isConfirmBulkOpen, setIsConfirmBulkOpen] = useState(false);
    const [pendingActionId, setPendingActionId] = useState<string | null>(null);
    const pendingAction =
        pendingActionId === null
            ? null
            : (bulkActions.find((action) => action.id === pendingActionId) ??
              null);
    const bulkActionStates = bulkActions
        .map((action) => {
            const context = {
                listSelectionCount: selectedRowCount,
                listSearchTerm: globalFilter,
                listFilterState: serverFilterState,
            };
            return {
                action,
                visible: flatpackActionVisibilityState(action, context),
                inactive: flatpackActionEnabledState(action, context),
            };
        })
        .filter(({ visible }) => visible.visible);
    const hasEnabledBulkAction = bulkActionStates.some(
        ({ inactive }) => !inactive.inactive,
    );

    return (
        <div className="flex min-h-8 flex-wrap items-center justify-between gap-3">
            <div className="flex flex-wrap items-center gap-2">
                {hasToolbarActions &&
                    toolbarActions.map((action) => (
                        <span
                            key={action.id}
                            title={
                                toolbarActionsDisabled
                                    ? toolbarDisabledHint
                                    : undefined
                            }
                            className="inline-flex max-w-full"
                        >
                            <Button
                                type="button"
                                variant={
                                    action.variant === 'destructive'
                                        ? 'destructive'
                                        : action.variant === 'default'
                                          ? 'default'
                                          : action.variant === 'secondary'
                                            ? 'secondary'
                                            : 'outline'
                                }
                                size="sm"
                                disabled={toolbarActionsDisabled}
                                onClick={() => onToolbarAction(action.id)}
                            >
                                {action.icon ? (
                                    <LucideIconByName
                                        name={action.icon}
                                        data-icon="inline-start"
                                    />
                                ) : null}
                                {action.label}
                            </Button>
                        </span>
                    ))}
                {hasToolbarActions && hasBulkActions ? (
                    <span
                        aria-hidden="true"
                        className="mx-1 hidden h-6 w-px shrink-0 bg-border sm:inline-block"
                    />
                ) : null}
                {hasBulkActions && (
                    <>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        selectedRowCount === 0 ||
                                        !hasEnabledBulkAction
                                    }
                                >
                                    Bulk Actions
                                    <ChevronDownIcon data-icon="inline-end" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                forceMount
                                align="start"
                                className="duration-0 data-open:animate-none data-closed:animate-none data-open:fade-in-0 data-open:zoom-in-100 data-closed:fade-out-0 data-closed:zoom-out-100"
                            >
                                {bulkActionStates.map(
                                    ({ action, inactive }) => (
                                        <DropdownMenuItem
                                            key={action.id}
                                            disabled={inactive.inactive}
                                            title={inactive.message}
                                            variant={
                                                action.variant === 'destructive'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                            onSelect={() => {
                                                if (inactive.inactive) {
                                                    return;
                                                }
                                                if (action.confirm === true) {
                                                    setPendingActionId(
                                                        action.id,
                                                    );
                                                    setIsConfirmBulkOpen(true);
                                                    return;
                                                }
                                                onBulkAction(action.id);
                                            }}
                                        >
                                            {action.icon ? (
                                                <LucideIconByName
                                                    name={action.icon}
                                                />
                                            ) : null}
                                            {action.label}
                                        </DropdownMenuItem>
                                    ),
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                        {selectedRowCount > 0 && (
                            <div className="flex items-center gap-2">
                                <div className="text-sm text-muted-foreground">
                                    Selected rows: {selectedRowCount}
                                </div>
                                {isAllRowsSelected ? (
                                    <Button
                                        variant="link"
                                        size="sm"
                                        onClick={onDeselectAllRows}
                                    >
                                        Deselect all
                                    </Button>
                                ) : totalRowCount > selectedRowCount ? (
                                    <Button
                                        variant="link"
                                        size="sm"
                                        onClick={onSelectAllRows}
                                    >
                                        Select all
                                    </Button>
                                ) : null}
                            </div>
                        )}
                    </>
                )}
            </div>
            <div className="flex items-center gap-2">
                {hasSearchableColumns && (
                    <DataTableSearchInput
                        id={id}
                        value={globalFilter}
                        onChange={onGlobalFilterChange}
                    />
                )}
                {hasFilters && (
                    <DataTableFiltersDropdown
                        id={id}
                        serverFilters={serverFilters}
                        serverFilterState={serverFilterState}
                        onSetSingleFilter={onSetSingleFilter}
                        onToggleMultiFilterValue={onToggleMultiFilterValue}
                        onSetDateFilter={onSetDateFilter}
                    />
                )}
                <DataTableColumnsVisibilityDropdown table={table} />
            </div>
            <FlatpackConfirmDialog
                open={isConfirmBulkOpen}
                onOpenChange={(open) => {
                    setIsConfirmBulkOpen(open);
                    if (!open) {
                        setPendingActionId(null);
                    }
                }}
                title={pendingAction?.label ?? 'Confirm'}
                continueVariant={
                    pendingAction?.variant === 'destructive'
                        ? 'destructive'
                        : 'default'
                }
                onContinue={() => {
                    if (pendingActionId !== null) {
                        onBulkAction(pendingActionId);
                    }
                    setIsConfirmBulkOpen(false);
                    setPendingActionId(null);
                }}
            />
        </div>
    );
}
