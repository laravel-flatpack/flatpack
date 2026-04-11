import { useState } from 'react';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';

export type SelectFieldOption = { value: string; label: string };

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
                        className="w-full max-w-sm"
                        aria-labelledby={label ? labelId : undefined}
                    >
                        <SelectValue placeholder={placeholder} />
                    </SelectTrigger>
                    <SelectContent>
                        {options.map((opt) => (
                            <SelectItem key={opt.value} value={opt.value}>
                                {opt.label}
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
