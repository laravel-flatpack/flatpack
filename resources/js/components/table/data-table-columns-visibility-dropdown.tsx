import type { Table as TanStackTable } from '@tanstack/react-table';
import { ChevronDownIcon, Columns3Icon } from 'lucide-react';
import { columnVisibilityMenuLabel } from '@/components/table/data-table-column-visibility';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type DataTableColumnsVisibilityDropdownProps = {
    table: TanStackTable<Record<string, unknown>>;
};

export function DataTableColumnsVisibilityDropdown({
    table,
}: DataTableColumnsVisibilityDropdownProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                    <Columns3Icon data-icon="inline-start" />
                    Columns
                    <ChevronDownIcon data-icon="inline-end" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                {table
                    .getAllColumns()
                    .filter(
                        (column) =>
                            typeof column.accessorFn !== 'undefined' &&
                            column.getCanHide(),
                    )
                    .map((column) => (
                        <DropdownMenuCheckboxItem
                            key={column.id}
                            checked={column.getIsVisible()}
                            onCheckedChange={(value) =>
                                column.toggleVisibility(!!value)
                            }
                        >
                            {columnVisibilityMenuLabel(column)}
                        </DropdownMenuCheckboxItem>
                    ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
