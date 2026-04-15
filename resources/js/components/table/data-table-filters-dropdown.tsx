import { ChevronDownIcon, FilterIcon } from 'lucide-react';
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
import { DatePickerField, SelectField } from '../form-fields';
import { Separator } from '../ui/separator';

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
    const getSelectedMultiValues = (filterId: string): string[] => {
        const selectedValue = serverFilterState[filterId];
        if (Array.isArray(selectedValue)) {
            return selectedValue;
        }
        if (typeof selectedValue === 'string' && selectedValue !== '') {
            return [selectedValue];
        }
        return [];
    };

    const resetAllFilters = () => {
        for (const filter of serverFilters) {
            if (filter.type === 'select') {
                if (filter.multiple) {
                    const selected = getSelectedMultiValues(filter.id);
                    for (const optionValue of selected) {
                        onToggleMultiFilterValue(filter.id, optionValue);
                    }
                    continue;
                }

                const selectedValue = serverFilterState[filter.id];
                if (typeof selectedValue === 'string' && selectedValue !== '') {
                    onSetSingleFilter(filter.id, '');
                }
                continue;
            }

            const dateValue = serverFilterState[filter.id];
            if (typeof dateValue === 'string' && dateValue !== '') {
                onSetDateFilter(filter.id);
            }
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                    <FilterIcon data-icon="inline-start" />
                    Filters
                    <ChevronDownIcon data-icon="inline-end" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-full min-w-80">
                <div id="data-table-filters-form" className="px-4 py-2">
                    <div className="flex items-center justify-between">
                        <h3 className="text-base font-semibold">Filters</h3>
                        <Button
                            variant="link"
                            size="sm"
                            className="px-0"
                            onClick={resetAllFilters}
                        >
                            Reset all
                        </Button>
                    </div>
                    <Separator className="my-2" />
                    <div className="flex flex-col gap-4">
                        {serverFilters.map((filter) => {
                            const fieldId = `${id}-filter-${filter.id}`;
                            const placeholder =
                                filter.placeholder ?? filter.label;

                            if (filter.type === 'select') {
                                const options = filter.options ?? [];
                                const selectedValue =
                                    serverFilterState[filter.id];
                                const singleValue =
                                    typeof selectedValue === 'string'
                                        ? selectedValue
                                        : null;
                                const selectedMultiValues =
                                    getSelectedMultiValues(filter.id);

                                return (
                                    <SelectField
                                        key={filter.id}
                                        id={fieldId}
                                        label={filter.label}
                                        placeholder={placeholder}
                                        options={options}
                                        multiple={Boolean(filter.multiple)}
                                        value={
                                            filter.multiple
                                                ? selectedMultiValues
                                                : singleValue
                                        }
                                        onValueChange={(
                                            nextValue: string[] | string | null,
                                        ) => {
                                            if (filter.multiple) {
                                                const nextSelectedValues =
                                                    Array.isArray(nextValue)
                                                        ? nextValue
                                                        : [];
                                                const currentSet = new Set(
                                                    selectedMultiValues,
                                                );
                                                const nextSet = new Set(
                                                    nextSelectedValues,
                                                );
                                                const allValues = new Set([
                                                    ...selectedMultiValues,
                                                    ...nextSelectedValues,
                                                ]);
                                                for (const optionValue of allValues) {
                                                    if (
                                                        currentSet.has(
                                                            optionValue,
                                                        ) !==
                                                        nextSet.has(optionValue)
                                                    ) {
                                                        onToggleMultiFilterValue(
                                                            filter.id,
                                                            optionValue,
                                                        );
                                                    }
                                                }
                                                return;
                                            }

                                            onSetSingleFilter(
                                                filter.id,
                                                typeof nextValue === 'string'
                                                    ? nextValue
                                                    : '',
                                            );
                                        }}
                                    />
                                );
                            }

                            const rawDateValue = serverFilterState[filter.id];
                            const dateValue =
                                typeof rawDateValue === 'string'
                                    ? rawDateValue
                                    : '';
                            const selectedDate = dateValue
                                ? new Date(`${dateValue}T00:00:00`)
                                : undefined;

                            return (
                                <DatePickerField
                                    key={filter.id}
                                    id={fieldId}
                                    label={filter.label}
                                    emptyLabel={placeholder}
                                    value={selectedDate}
                                    onValueChange={(date) =>
                                        onSetDateFilter(filter.id, date)
                                    }
                                />
                            );
                        })}
                    </div>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
