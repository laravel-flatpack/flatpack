import { FormActionsRenderer } from '@/components/actions/renderer';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import { useFlatpackFormInlineActions } from '@/contexts/flatpack-form-inline-actions';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import type {
    FormFieldLabelShow,
    ToolbarFieldAlign,
} from '@/types/form-fields';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

export const ToolbarField = ({
    id,
    label,
    helperText,
    showLabel,
    actions,
    align = 'right',
}: {
    id: string;
    label?: string;
    helperText?: string;
    showLabel?: FormFieldLabelShow;
    actions: FlatpackListHeaderAction[];
    align?: ToolbarFieldAlign;
}) => {
    const ctx = useFlatpackFormInlineActions();

    if (ctx === null || actions.length === 0) {
        return null;
    }

    const row = (
        <FormActionsRenderer
            actions={actions}
            formId={ctx.formId}
            formProcessing={ctx.formProcessing}
            formIsDirty={ctx.formIsDirty}
            formMode={ctx.mode}
            fieldsLength={ctx.fieldsLength}
            formValues={ctx.formValues}
            onFormSubmitIntent={ctx.prepareFormSubmit}
            onFormSubmitConfirmClick={(action) =>
                ctx.requestConfirmForAction(action)
            }
            onRunAction={ctx.requestRunAction}
            align={align}
            shortcutRegistration={{
                scope: `toolbar-${id}`,
                actions,
            }}
        />
    );

    const trimmedLabel = typeof label === 'string' ? label.trim() : '';
    if (trimmedLabel === '') {
        return <div className="w-full min-w-0">{row}</div>;
    }

    const labelId = `${id}-label`;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');

    return (
        <Field orientation={labelLayout.orientation} className="w-full min-w-0">
            <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                {trimmedLabel}
            </FieldTitle>
            <FieldContent>
                {row}
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
