import { Field, FieldContent, FieldDescription, FieldLabel } from '../ui/field';
import { Input } from '../ui/input';

export const TextField = ({
    id,
    label,
    placeholder,
    helperText,
    defaultValue = '',
}: {
    id: string;
    label: string;
    placeholder: string;
    helperText?: string;
    defaultValue?: string;
}) => {
    return (
        <Field>
            {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
            <FieldContent>
                <Input
                    id={id}
                    type="text"
                    placeholder={placeholder}
                    defaultValue={defaultValue}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
