import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import {
    formatInputValue,
    formatInputValueOnBlur,
} from '@/lib/form-field-preset';
import { cn } from '@/lib/utils';
import type {
    FormFieldInputFormat,
    FormFieldLabelShow,
} from '@/types/form-fields';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import { Input } from '../ui/input';

export const TextField = ({
    id,
    label,
    placeholder,
    helperText,
    defaultValue = '',
    value,
    onValueChange,
    onBlur,
    onKeyDown,
    format,
    inline = false,
    showLabel,
    inputClassName,
    required = false,
    invalid = false,
    disabled = false,
}: {
    id: string;
    label: string;
    placeholder: string;
    helperText?: string;
    defaultValue?: string;
    value?: string;
    onValueChange?: (value: string) => void;
    onBlur?: React.FocusEventHandler<HTMLInputElement>;
    onKeyDown?: React.KeyboardEventHandler<HTMLInputElement>;
    format?: FormFieldInputFormat;
    inline?: boolean;
    showLabel?: FormFieldLabelShow;
    inputClassName?: string;
    required?: boolean;
    invalid?: boolean;
    disabled?: boolean;
}) => {
    const labelId = `${id}-label`;
    const labelledBy = !inline && label ? labelId : undefined;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');

    const input = (
        <Input
            id={id}
            name={id}
            type="text"
            placeholder={placeholder}
            defaultValue={value === undefined ? defaultValue : undefined}
            value={value}
            aria-labelledby={labelledBy}
            aria-invalid={invalid || undefined}
            required={required}
            disabled={disabled}
            className={cn(inputClassName)}
            onChange={(e) =>
                onValueChange?.(
                    format
                        ? formatInputValue(e.target.value, format)
                        : e.target.value,
                )
            }
            onBlur={(event) => {
                if (format) {
                    onValueChange?.(
                        formatInputValueOnBlur(event.target.value, format),
                    );
                }
                onBlur?.(event);
            }}
            onKeyDown={onKeyDown}
            autoComplete="off"
            autoCorrect="off"
            autoCapitalize="none"
            spellCheck={false}
        />
    );

    if (inline) {
        return input;
    }

    return (
        <Field orientation={labelLayout.orientation}>
            {label ? (
                <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                    {label}
                </FieldTitle>
            ) : null}
            <FieldContent>
                {input}
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
