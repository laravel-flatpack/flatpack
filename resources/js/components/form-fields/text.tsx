import { formatInputValue, formatInputValueOnBlur } from '@/lib/form-field-preset';
import { cn } from '@/lib/utils';
import type { FormFieldInputFormat } from '@/types/form-fields';
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
    inputClassName,
    required = false,
    invalid = false,
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
    inputClassName?: string;
    required?: boolean;
    invalid?: boolean;
}) => {
    const labelId = `${id}-label`;
    const labelledBy = !inline && label ? labelId : undefined;

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
                    onValueChange?.(formatInputValueOnBlur(event.target.value, format));
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
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                {input}
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
