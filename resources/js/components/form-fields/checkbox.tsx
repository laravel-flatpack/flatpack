import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import type { FormFieldLabelShow } from '@/types/form-fields';
import { Checkbox } from '../ui/checkbox';
import { Field, FieldContent, FieldTitle } from '../ui/field';

export const CheckboxField = ({
    id,
    label,
    defaultChecked,
    checked,
    showLabel,
    onValueChange,
    disabled = false,
}: {
    id: string;
    label: string;
    defaultChecked?: boolean;
    checked?: boolean;
    showLabel?: FormFieldLabelShow;
    onValueChange?: (checked: boolean) => void;
    disabled?: boolean;
}) => {
    const labelId = `${id}-label`;
    const ll = resolveFormFieldLabelLayout(showLabel, 'inline');

    const control = (
        <Checkbox
            id={id}
            defaultChecked={checked === undefined ? defaultChecked : undefined}
            checked={checked}
            disabled={disabled}
            aria-labelledby={label ? labelId : undefined}
            onCheckedChange={(c) => onValueChange?.(c === true)}
        />
    );

    if (ll.orientation === 'vertical') {
        return (
            <Field orientation="vertical">
                {label ? (
                    <FieldTitle id={labelId} className={ll.labelClassName}>
                        {label}
                    </FieldTitle>
                ) : null}
                <FieldContent>{control}</FieldContent>
            </Field>
        );
    }

    return (
        <Field orientation="horizontal">
            {control}
            {label ? (
                <FieldTitle id={labelId} className={ll.labelClassName}>
                    {label}
                </FieldTitle>
            ) : null}
        </Field>
    );
};
