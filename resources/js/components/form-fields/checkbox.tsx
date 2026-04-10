import { Checkbox } from '../ui/checkbox';
import { Field, FieldTitle } from '../ui/field';

export const CheckboxField = ({
    id,
    label,
    defaultChecked,
}: {
    id: string;
    label: string;
    defaultChecked?: boolean;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field orientation="horizontal">
            <Checkbox
                id={id}
                defaultChecked={defaultChecked}
                aria-labelledby={label ? labelId : undefined}
            />
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
        </Field>
    );
};
