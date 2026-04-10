import { cn } from '@/lib/utils';
import { Field, FieldContent, FieldDescription, FieldLabel } from '../ui/field';
import { Textarea } from '../ui/textarea';

export const TextareaField = ({
    id,
    label,
    placeholder,
    rows = 4,
    helperText,
    defaultValue = '',
    className,
}: {
    id: string;
    label: string;
    placeholder: string;
    rows?: number;
    helperText?: string;
    defaultValue?: string;
    className?: string;
}) => {
    return (
        <Field>
            {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
            <FieldContent>
                <Textarea
                    id={id}
                    placeholder={placeholder}
                    defaultValue={defaultValue}
                    rows={rows}
                    className={cn(className)}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
