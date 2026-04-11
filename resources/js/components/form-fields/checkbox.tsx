import { Checkbox } from '../ui/checkbox';
import { Field, FieldTitle } from '../ui/field';

export const CheckboxField = ({
    id,
    label,
    defaultChecked,
    onValueChange,
}: {
    id: string;
    label: string;
    defaultChecked?: boolean;
    onValueChange?: (checked: boolean) => void;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field orientation="horizontal">
            <Checkbox
                id={id}
                defaultChecked={defaultChecked}
                aria-labelledby={label ? labelId : undefined}
                onCheckedChange={(c) => onValueChange?.(c === true)}
            />
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
        </Field>
    );
};
