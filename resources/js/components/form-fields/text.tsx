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
}) => {
    const labelId = `${id}-label`;

    const input = (
        <Input
            id={id}
            type="text"
            placeholder={placeholder}
            defaultValue={value === undefined ? defaultValue : undefined}
            value={value}
            aria-labelledby={label ? labelId : undefined}
            className={cn(inputClassName)}
            onChange={(e) => onValueChange?.(e.target.value)}
            onBlur={onBlur}
            onKeyDown={onKeyDown}
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
