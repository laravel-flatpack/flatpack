import { Checkbox } from '../ui/checkbox';
import { Field, FieldTitle } from '../ui/field';

export const CheckboxField = ({
    id,
    label,
    defaultChecked,
    checked,
    onValueChange,
}: {
    id: string;
    label: string;
    defaultChecked?: boolean;
    checked?: boolean;
    onValueChange?: (checked: boolean) => void;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field orientation="horizontal">
            <Checkbox
                id={id}
                defaultChecked={
                    checked === undefined ? defaultChecked : undefined
                }
                checked={checked}
                aria-labelledby={label ? labelId : undefined}
                onCheckedChange={(c) => onValueChange?.(c === true)}
            />
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
        </Field>
    );
};
