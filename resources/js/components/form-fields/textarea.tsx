import { cn } from '@/lib/utils';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import { Textarea } from '../ui/textarea';

export const TextareaField = ({
    id,
    label,
    placeholder,
    rows = 4,
    helperText,
    defaultValue = '',
    className,
    onValueChange,
}: {
    id: string;
    label: string;
    placeholder: string;
    rows?: number;
    helperText?: string;
    defaultValue?: string;
    className?: string;
    onValueChange?: (value: string) => void;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <Textarea
                    id={id}
                    placeholder={placeholder}
                    defaultValue={defaultValue}
                    rows={rows}
                    className={cn(className)}
                    aria-labelledby={label ? labelId : undefined}
                    onChange={(e) => onValueChange?.(e.target.value)}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
