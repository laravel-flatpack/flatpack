import {
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronsLeftIcon,
    ChevronsRightIcon,
} from 'lucide-react';
import { DATA_TABLE_PAGE_SIZE_OPTIONS } from '@/components/table/data-table-constants';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { DataTableFooterProps } from '@/types/table';

export function DataTableFooter({
    id,
    rowCountLabel,
    pageSize,
    pageIndex,
    pageCount,
    canPreviousPage,
    canNextPage,
    onPageSizeChange,
    onFirstPage,
    onPreviousPage,
    onNextPage,
    onLastPage,
}: DataTableFooterProps) {
    return (
        <div className="flex flex-col gap-4 px-1 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-muted-foreground">{rowCountLabel}</div>
            <div className="flex w-full flex-col gap-4 sm:w-auto sm:flex-row sm:items-center sm:gap-6">
                <div className="flex items-center gap-2">
                    <Label
                        htmlFor={`${id}-rows-per-page`}
                        className="text-sm font-medium whitespace-nowrap"
                    >
                        Rows per page
                    </Label>
                    <Select
                        value={`${pageSize}`}
                        onValueChange={onPageSizeChange}
                    >
                        <SelectTrigger
                            size="sm"
                            className="w-20"
                            id={`${id}-rows-per-page`}
                        >
                            <SelectValue placeholder={pageSize} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            <SelectGroup>
                                {DATA_TABLE_PAGE_SIZE_OPTIONS.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={`${option}`}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div className="flex items-center justify-center gap-2 text-sm font-medium">
                    <span className="whitespace-nowrap">
                        Page {pageIndex + 1} of {pageCount || 1}
                    </span>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            className="hidden size-8 p-0 sm:flex"
                            onClick={onFirstPage}
                            disabled={!canPreviousPage}
                        >
                            <span className="sr-only">First page</span>
                            <ChevronsLeftIcon className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="size-8"
                            size="icon"
                            onClick={onPreviousPage}
                            disabled={!canPreviousPage}
                        >
                            <span className="sr-only">Previous page</span>
                            <ChevronLeftIcon className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="size-8"
                            size="icon"
                            onClick={onNextPage}
                            disabled={!canNextPage}
                        >
                            <span className="sr-only">Next page</span>
                            <ChevronRightIcon className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            className="hidden size-8 sm:flex"
                            size="icon"
                            onClick={onLastPage}
                            disabled={!canNextPage}
                        >
                            <span className="sr-only">Last page</span>
                            <ChevronsRightIcon className="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
