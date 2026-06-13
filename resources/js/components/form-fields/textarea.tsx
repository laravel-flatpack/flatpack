import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import { cn } from '@/lib/utils';
import type { FormFieldLabelShow } from '@/types/form-fields';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import { Textarea } from '../ui/textarea';

export const TextareaField = ({
    id,
    label,
    placeholder,
    rows = 4,
    helperText,
    defaultValue = '',
    value,
    className,
    onValueChange,
    showLabel,
    required = false,
    invalid = false,
    disabled = false,
}: {
    id: string;
    label: string;
    placeholder: string;
    rows?: number;
    helperText?: string;
    defaultValue?: string;
    value?: string;
    className?: string;
    onValueChange?: (value: string) => void;
    showLabel?: FormFieldLabelShow;
    required?: boolean;
    invalid?: boolean;
    disabled?: boolean;
}) => {
    const labelId = `${id}-label`;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');
    return (
        <Field orientation={labelLayout.orientation}>
            {label ? (
                <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                    {label}
                </FieldTitle>
            ) : null}
            <FieldContent>
                <Textarea
                    id={id}
                    name={id}
                    placeholder={placeholder}
                    defaultValue={
                        value === undefined ? defaultValue : undefined
                    }
                    value={value}
                    rows={rows}
                    className={cn(className)}
                    aria-labelledby={label ? labelId : undefined}
                    aria-invalid={invalid || undefined}
                    autoComplete="off"
                    required={required}
                    disabled={disabled}
                    onChange={(e) => onValueChange?.(e.target.value)}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
