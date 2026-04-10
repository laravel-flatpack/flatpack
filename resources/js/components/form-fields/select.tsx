import { useState } from 'react';
import { Field, FieldContent, FieldDescription, FieldLabel } from '../ui/field';
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
}: {
    id: string;
    label: string;
    placeholder: string;
    options: SelectFieldOption[];
    helperText?: string;
}) => {
    const [value, setValue] = useState(options[0]?.value ?? '');

    return (
        <Field>
            {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
            <FieldContent>
                <Select value={value} onValueChange={setValue}>
                    <SelectTrigger id={id} className="w-full max-w-sm">
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
