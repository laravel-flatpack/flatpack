import { ChevronDownIcon } from 'lucide-react';
import { useLayoutEffect, useMemo, useState } from 'react';
import { selectOptionLeadingIcon } from '@/components/select-option-leading-icon';
import { CLEAR_SELECT_ITEM_VALUE } from '@/lib/flatpack-select';
import type { SelectFieldOption } from '@/types/form-fields';
import { Badge } from '../ui/badge';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';

export type { SelectFieldOption };

type SelectFieldBaseProps = {
    id: string;
    label: string;
    placeholder: string;
    options: SelectFieldOption[];
    helperText?: string;
    required?: boolean;
    invalid?: boolean;
};

type SelectFieldSingleProps = SelectFieldBaseProps & {
    multiple?: false;
    value?: unknown;
    onValueChange?: (value: string | null) => void;
};

type SelectFieldMultipleProps = SelectFieldBaseProps & {
    multiple: true;
    value?: unknown;
    onValueChange?: (value: string[]) => void;
};

type SelectFieldProps = SelectFieldSingleProps | SelectFieldMultipleProps;

function selectValueFromUnknown(
    raw: unknown,
    options: SelectFieldOption[],
): string {
    if (raw == null) {
        return '';
    }
    const s = String(raw);
    return options.some((o) => o.value === s) ? s : '';
}

function selectValuesFromUnknown(
    raw: unknown,
    options: SelectFieldOption[],
): string[] {
    if (!Array.isArray(raw)) {
        return [];
    }
    const optionValues = new Set(options.map((option) => option.value));
    return raw
        .map((value) => String(value))
        .filter((value) => optionValues.has(value));
}

function buildMultiTriggerLabel(
    selectedLabels: string[],
    placeholder: string,
): string {
    if (selectedLabels.length === 0) {
        return placeholder;
    }
    if (selectedLabels.length <= 2) {
        return selectedLabels.join(', ');
    }
    return `${selectedLabels.slice(0, 2).join(', ')} +${selectedLabels.length - 2}`;
}

export const SelectField = ({
    id,
    label,
    placeholder,
    options = [],
    multiple = false,
    value: valueProp,
    helperText,
    onValueChange,
    invalid = false,
}: SelectFieldProps) => {
    const [singleValue, setSingleValue] = useState<string>(() =>
        selectValueFromUnknown(valueProp, options),
    );
    const [multiValue, setMultiValue] = useState<string[]>(() =>
        selectValuesFromUnknown(valueProp, options),
    );

    useLayoutEffect(() => {
        if (multiple) {
            setMultiValue(selectValuesFromUnknown(valueProp, options));
            return;
        }
        setSingleValue(selectValueFromUnknown(valueProp, options));
    }, [multiple, valueProp, options]);

    const labelId = `${id}-label`;
    const selectedCount = multiValue.length;
    const selectedLabels = useMemo(
        () =>
            options
                .filter((option) => multiValue.includes(option.value))
                .map((option) => option.label),
        [options, multiValue],
    );
    const selectedOptions = useMemo(
        () => options.filter((option) => multiValue.includes(option.value)),
        [options, multiValue],
    );
    const multiTriggerLabel = buildMultiTriggerLabel(
        selectedLabels,
        placeholder,
    );
    const previewOptions = selectedOptions.slice(0, 3);
    const emitMultiValueChange = (value: string[]) => {
        if (multiple) {
            (onValueChange as SelectFieldMultipleProps['onValueChange'])?.(
                value,
            );
        }
    };
    const emitSingleValueChange = (value: string | null) => {
        if (!multiple) {
            (onValueChange as SelectFieldSingleProps['onValueChange'])?.(value);
        }
    };

    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                {multiple ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                id={id}
                                type="button"
                                className="flex h-9 w-full min-w-0 items-center justify-between rounded-3xl border border-transparent bg-input/50 px-3 py-2 text-left text-sm transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30"
                                aria-labelledby={label ? labelId : undefined}
                                aria-invalid={invalid || undefined}
                            >
                                <span className="flex min-w-0 items-center gap-2">
                                    {selectedCount > 0 ? (
                                        <span className="flex -space-x-1">
                                            {previewOptions.map((option) => (
                                                <span
                                                    key={`preview-${option.value}`}
                                                    className="inline-flex h-4 w-4 items-center justify-center rounded-full bg-background ring-1 ring-border"
                                                >
                                                    {selectOptionLeadingIcon(
                                                        option,
                                                    )}
                                                </span>
                                            ))}
                                        </span>
                                    ) : null}
                                    <span
                                        className={
                                            selectedCount === 0
                                                ? 'truncate text-muted-foreground'
                                                : 'truncate'
                                        }
                                    >
                                        {multiTriggerLabel}
                                    </span>
                                </span>
                                <span className="ml-2 flex items-center gap-1.5">
                                    {selectedCount > 0 && (
                                        <Badge
                                            variant="default"
                                            className="h-4 rounded-full px-2 text-xs"
                                        >
                                            {selectedCount}/{options.length}
                                        </Badge>
                                    )}
                                    <ChevronDownIcon className="size-4 text-muted-foreground" />
                                </span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-64" align="start">
                            <DropdownMenuLabel>
                                {selectedCount > 0
                                    ? `${selectedCount} selected`
                                    : 'No selection'}
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {options.map((opt) => (
                                <DropdownMenuCheckboxItem
                                    key={opt.value}
                                    checked={multiValue.includes(opt.value)}
                                    onCheckedChange={() => {
                                        const nextValue = multiValue.includes(
                                            opt.value,
                                        )
                                            ? multiValue.filter(
                                                  (value) =>
                                                      value !== opt.value,
                                              )
                                            : [...multiValue, opt.value];
                                        setMultiValue(nextValue);
                                        emitMultiValueChange(nextValue);
                                    }}
                                >
                                    <span className="flex items-center gap-2">
                                        {selectOptionLeadingIcon(opt)}
                                        <span>{opt.label}</span>
                                    </span>
                                </DropdownMenuCheckboxItem>
                            ))}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                disabled={selectedCount === 0}
                                onSelect={(event) => {
                                    event.preventDefault();
                                    setMultiValue([]);
                                    emitMultiValueChange([]);
                                }}
                            >
                                Clear selection
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : (
                    <Select
                        value={singleValue}
                        onValueChange={(v) => {
                            if (v === CLEAR_SELECT_ITEM_VALUE) {
                                setSingleValue('');
                                emitSingleValueChange(null);
                                return;
                            }
                            setSingleValue(v);
                            emitSingleValueChange(v);
                        }}
                    >
                        <SelectTrigger
                            id={id}
                            className="w-full"
                            aria-labelledby={label ? labelId : undefined}
                            aria-invalid={invalid || undefined}
                        >
                            <SelectValue placeholder={placeholder} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={CLEAR_SELECT_ITEM_VALUE}>
                                <span className="text-muted-foreground">
                                    {placeholder}
                                </span>
                            </SelectItem>
                            {options.map((opt) => (
                                <SelectItem key={opt.value} value={opt.value}>
                                    <span className="flex items-center gap-2">
                                        {selectOptionLeadingIcon(opt)}
                                        <span>{opt.label}</span>
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
