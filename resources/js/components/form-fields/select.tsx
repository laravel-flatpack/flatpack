import { useLayoutEffect, useState } from 'react';
import { CLEAR_SELECT_ITEM_VALUE } from '@/lib/flatpack-select';
import { selectOptionLeadingIcon } from '@/lib/select-option-leading-icon';
import type { SelectFieldOption } from '@/types/form-fields';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';

export type { SelectFieldOption };

/** Radix root `value`: use `''` for empty (placeholder); `undefined` breaks clear-item clicks. */
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

/** Clear via first menu row (placeholder label) or set `value={null}` from parent. */
export const SelectField = ({
    id,
    label,
    placeholder,
    options,
    value: valueProp,
    helperText,
    onValueChange,
}: {
    id: string;
    label: string;
    placeholder: string;
    options: SelectFieldOption[];
    value?: unknown;
    helperText?: string;
    onValueChange?: (value: unknown) => void;
}) => {
    const [value, setValue] = useState<string>(() =>
        selectValueFromUnknown(valueProp, options),
    );

    useLayoutEffect(() => {
        setValue(selectValueFromUnknown(valueProp, options));
    }, [valueProp, options]);

    const labelId = `${id}-label`;

    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <Select
                    value={value}
                    onValueChange={(v) => {
                        if (v === CLEAR_SELECT_ITEM_VALUE) {
                            setValue('');
                            onValueChange?.(null);
                            return;
                        }
                        setValue(v);
                        onValueChange?.(v);
                    }}
                >
                    <SelectTrigger
                        id={id}
                        className="w-full"
                        aria-labelledby={label ? labelId : undefined}
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
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
