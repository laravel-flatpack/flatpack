import { cn } from '@/lib/utils';
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
    inline?: boolean;
    inputClassName?: string;
    required?: boolean;
    invalid?: boolean;
}) => {
    const labelId = `${id}-label`;

    const input = (
        <Input
            id={id}
            name={`${id}-input`}
            type="text"
            placeholder={placeholder}
            defaultValue={value === undefined ? defaultValue : undefined}
            value={value}
            aria-labelledby={label ? labelId : undefined}
            aria-invalid={invalid || undefined}
            required={required}
            className={cn(inputClassName)}
            onChange={(e) => onValueChange?.(e.target.value)}
            onBlur={onBlur}
            onKeyDown={onKeyDown}
            autoComplete="new-password"
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
