import { useState } from 'react';
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

export const SelectField = ({
    id,
    label,
    placeholder,
    options,
    helperText,
    onValueChange,
}: {
    id: string;
    label: string;
    placeholder: string;
    options: SelectFieldOption[];
    helperText?: string;
    onValueChange?: (value: string) => void;
}) => {
    const [value, setValue] = useState(options[0]?.value ?? '');
    const labelId = `${id}-label`;

    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <Select
                    value={value}
                    onValueChange={(v) => {
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
