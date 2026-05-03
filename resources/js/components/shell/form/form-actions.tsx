import { FormActionsRenderer } from '@/components/actions/form-actions-renderer';
import type { FlatpackListSubmitAction } from '@/types/flatpack-actions';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type FormActionsProps = {
    formActions: FlatpackListHeaderAction[];
    formId: string;
    formProcessing: boolean;
    formIsDirty?: boolean;
    formMode: 'create' | 'edit';
    fieldsLength: number;
    /** Sets which YAML row / handler name is sent on the next {@code POST …/submit}. */
    onFormSubmitIntent: (action: FlatpackListSubmitAction) => void;
    /** Opens confirm dialog for toolbar rows with {@code confirm: true}. */
    onFormSubmitConfirmClick: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    /** Runs the selected toolbar action immediately (submit or non-submit path). */
    onRunAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
};

export function FormActions({
    formActions,
    formId,
    formProcessing,
    formIsDirty = true,
    formMode,
    fieldsLength,
    onFormSubmitIntent,
    onFormSubmitConfirmClick,
    onRunAction,
}: FormActionsProps) {
    if (formActions.length === 0) {
        return null;
    }

    return (
        <FormActionsRenderer
            actions={formActions}
            formId={formId}
            formProcessing={formProcessing}
            formIsDirty={formIsDirty}
            formMode={formMode}
            fieldsLength={fieldsLength}
            onFormSubmitIntent={onFormSubmitIntent}
            onFormSubmitConfirmClick={onFormSubmitConfirmClick}
            onRunAction={onRunAction}
            align="right"
            shortcutRegistration={{
                scope: 'form-actions',
                actions: formActions,
            }}
        />
    );
}
