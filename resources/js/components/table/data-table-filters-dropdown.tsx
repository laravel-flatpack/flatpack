import { format } from 'date-fns';
import { CalendarIcon, ChevronDownIcon, FilterIcon, XIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    FlatpackDataTableFilter,
    FlatpackDataTableServerFiltersState,
} from '@/types/data-table';

const ALL_FILTER_OPTION_VALUE = '__all__';

type DataTableFiltersDropdownProps = {
    id: string;
    serverFilters: FlatpackDataTableFilter[];
    serverFilterState: FlatpackDataTableServerFiltersState;
    onSetSingleFilter: (filterId: string, value: string) => void;
    onToggleMultiFilterValue: (filterId: string, optionValue: string) => void;
    onSetDateFilter: (filterId: string, date?: Date) => void;
};

export function DataTableFiltersDropdown({
    id,
    serverFilters,
    serverFilterState,
    onSetSingleFilter,
    onToggleMultiFilterValue,
    onSetDateFilter,
}: DataTableFiltersDropdownProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                    <FilterIcon data-icon="inline-start" />
                    Filters
                    <ChevronDownIcon data-icon="inline-end" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                {serverFilters.map((filter) => {
                    if (filter.type === 'select') {
                        const selectedValue = serverFilterState[filter.id];
                        const options = filter.options ?? [];
                        if (filter.multiple) {
                            const selected = Array.isArray(selectedValue)
                                ? selectedValue
                                : selectedValue
                                  ? [selectedValue]
                                  : [];
                            return (
                                <DropdownMenu key={filter.id}>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="sm">
                                            {filter.placeholder ?? filter.label}
                                            {selected.length > 0
                                                ? ` (${selected.length})`
                                                : ''}
                                            <ChevronDownIcon data-icon="inline-end" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent className="w-56">
                                        {options.map((option) => (
                                            <DropdownMenuCheckboxItem
                                                key={`${filter.id}-${option.value}`}
                                                checked={selected.includes(
                                                    option.value,
                                                )}
                                                onCheckedChange={() =>
                                                    onToggleMultiFilterValue(
                                                        filter.id,
                                                        option.value,
                                                    )
                                                }
                                            >
                                                {option.label}
                                            </DropdownMenuCheckboxItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            );
                        }
                        const normalized =
                            typeof selectedValue === 'string'
                                ? selectedValue
                                : '';
                        return (
                            <div
                                key={filter.id}
                                className="flex items-center gap-1"
                            >
                                <Select
                                    value={
                                        normalized === ''
                                            ? ALL_FILTER_OPTION_VALUE
                                            : normalized
                                    }
                                    onValueChange={(value) =>
                                        onSetSingleFilter(
                                            filter.id,
                                            value === ALL_FILTER_OPTION_VALUE
                                                ? ''
                                                : value,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        size="sm"
                                        className="min-w-40"
                                        id={`${id}-filter-${filter.id}`}
                                    >
                                        <SelectValue
                                            placeholder={
                                                filter.placeholder ??
                                                filter.label
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                value={ALL_FILTER_OPTION_VALUE}
                                            >
                                                All {filter.label}
                                            </SelectItem>
                                            {options.map((option) => (
                                                <SelectItem
                                                    key={`${filter.id}-${option.value}`}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                {normalized !== '' ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        onClick={() =>
                                            onSetSingleFilter(filter.id, '')
                                        }
                                    >
                                        <span className="sr-only">
                                            Clear {filter.label}
                                        </span>
                                        <XIcon className="size-4" />
                                    </Button>
                                ) : null}
                            </div>
                        );
                    }

                    const dateValue =
                        typeof serverFilterState[filter.id] === 'string'
                            ? serverFilterState[filter.id]
                            : '';
                    const selectedDate = dateValue
                        ? new Date(`${dateValue}T00:00:00`)
                        : undefined;
                    return (
                        <div
                            key={filter.id}
                            className="flex items-center gap-1"
                        >
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <CalendarIcon data-icon="inline-start" />
                                        {selectedDate
                                            ? format(selectedDate, 'PPP')
                                            : (filter.placeholder ??
                                              filter.label)}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="w-auto p-0"
                                    align="start"
                                >
                                    <Calendar
                                        mode="single"
                                        selected={selectedDate}
                                        onSelect={(date) =>
                                            onSetDateFilter(filter.id, date)
                                        }
                                    />
                                </PopoverContent>
                            </Popover>
                            {selectedDate ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    onClick={() => onSetDateFilter(filter.id)}
                                >
                                    <span className="sr-only">
                                        Clear {filter.label}
                                    </span>
                                    <XIcon className="size-4" />
                                </Button>
                            ) : null}
                        </div>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
