import { Field, FieldLabel } from '../ui/field';
import { Switch } from '../ui/switch';

export const SwitchField = ({
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
            <Switch id={id} defaultChecked={defaultChecked} />
            {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
        </Field>
    );
};
