import type { Table as TanStackTable } from '@tanstack/react-table';
import { ChevronDownIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';
import { DataTableColumnsVisibilityDropdown } from '@/components/table/data-table-columns-visibility-dropdown';
import { DataTableFiltersDropdown } from '@/components/table/data-table-filters-dropdown';
import { DataTableSearchInput } from '@/components/table/data-table-search-input';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type {
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
} from '@/types/data-table';

type DataTableToolbarProps = {
    id: string;
    table: TanStackTable<Record<string, unknown>>;
    hasBulkActions: boolean;
    selectedRowCount: number;
    isAllRowsSelected: boolean;
    totalRowCount: number;
    onSelectAllRows: () => void;
    onDeselectAllRows: () => void;
    onDeleteSelectedRows: () => void | Promise<void>;
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
    hasBulkActions,
    selectedRowCount,
    isAllRowsSelected,
    totalRowCount,
    onSelectAllRows,
    onDeselectAllRows,
    onDeleteSelectedRows,
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
    const [isConfirmDeleteOpen, setIsConfirmDeleteOpen] = useState(false);

    return (
        <div className="h-8 flex items-center justify-between gap-3">
            <div className="flex items-center gap-2">
                {hasBulkActions && (
                    <>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={selectedRowCount === 0}
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
                                <DropdownMenuItem
                                    variant="destructive"
                                    onSelect={() => {
                                        setIsConfirmDeleteOpen(true);
                                    }}
                                >
                                    <Trash2Icon />
                                    Delete
                                </DropdownMenuItem>
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
            <AlertDialog
                open={isConfirmDeleteOpen}
                onOpenChange={setIsConfirmDeleteOpen}
            >
                <AlertDialogContent size="sm">
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Delete selected records?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            This will delete {selectedRowCount}{' '}
                            {selectedRowCount === 1 ? 'record' : 'records'}. Are
                            you sure you want to delete these records?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            onClick={() => {
                                onDeleteSelectedRows();
                                setIsConfirmDeleteOpen(false);
                            }}
                        >
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
