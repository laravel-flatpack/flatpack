import { Field, FieldTitle } from '../ui/field';
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
    const labelId = `${id}-label`;
    return (
        <Field orientation="horizontal">
            <Switch
                id={id}
                defaultChecked={defaultChecked}
                aria-labelledby={label ? labelId : undefined}
            />
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
        </Field>
    );
};
