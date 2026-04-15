import type { Table as TanStackTable } from '@tanstack/react-table';
import { ChevronDownIcon } from 'lucide-react';
import { DataTableColumnsVisibilityDropdown } from '@/components/table/data-table-columns-visibility-dropdown';
import { DataTableFiltersDropdown } from '@/components/table/data-table-filters-dropdown';
import { DataTableSearchInput } from '@/components/table/data-table-search-input';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
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
                            <DropdownMenuContent align="start" className="w-40">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="w-full justify-start"
                                    disabled
                                >
                                    No actions
                                </Button>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        {selectedRowCount > 0 && (
                            <div className="flex items-center gap-2">
                                <div className="text-sm text-muted-foreground">
                                    Selected rows: {selectedRowCount}
                                </div>
                                <Button variant="link" size="sm">
                                    Select all
                                </Button>
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
        </div>
    );
}
