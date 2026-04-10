import { Checkbox } from '../ui/checkbox';
import { Field, FieldLabel } from '../ui/field';

export const CheckboxField = ({
    id,
    label,
    defaultChecked,
}: {
    id: string;
    label: string;
    defaultChecked?: boolean;
}) => {
    return (
        <Field orientation="horizontal">
            <Checkbox id={id} defaultChecked={defaultChecked} />
            {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
        </Field>
    );
};
