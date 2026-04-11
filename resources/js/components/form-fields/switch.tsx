import { Field, FieldTitle } from '../ui/field';
import { Switch } from '../ui/switch';

export const SwitchField = ({
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
            <Switch
                id={id}
                defaultChecked={defaultChecked}
                aria-labelledby={label ? labelId : undefined}
                onCheckedChange={(c) => onValueChange?.(c === true)}
            />
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
        </Field>
    );
};
