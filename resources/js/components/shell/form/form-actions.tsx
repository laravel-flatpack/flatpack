import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    ActionButtonContent,
    actionIconButtonClass,
    actionVariant,
} from '@/components/actions/action-button-content';
import {
    ActionInactiveTooltip,
    actionEnabledState,
    actionVisibilityState,
} from '@/components/actions/action-dirty-guard';
import { Button } from '@/components/ui/button';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import { useFlatpackRegisterActionShortcuts } from '@/hooks/use-flatpack-register-action-shortcuts';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';
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
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();

    const showShortcutHintsOnButtons =
        flatpack.showActionShortcutHints === true;
    const [activeSubmittingActionId, setActiveSubmittingActionId] = useState<
        string | null
    >(null);

    const { shortcutByActionId, isMacPlatform } = useFlatpackActionShortcuts({
        actions: formActions,
    });

    useFlatpackRegisterActionShortcuts({
        scope: 'form-actions',
        actions: formActions,
        shortcutByActionId,
    });

    useEffect(() => {
        if (!formProcessing) {
            setActiveSubmittingActionId(null);
        }
    }, [formProcessing]);

    if (formActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-end gap-2">
            {formActions.map((action) => (
                <FormAction
                    key={action.id}
                    action={action}
                    formId={formId}
                    fieldsLength={fieldsLength}
                    formIsDirty={formIsDirty ?? true}
                    formMode={formMode}
                    formProcessing={formProcessing}
                    isMacPlatform={isMacPlatform}
                    shortcut={
                        showShortcutHintsOnButtons
                            ? shortcutByActionId.get(action.id)
                            : undefined
                    }
                    onFormSubmitConfirmClick={onFormSubmitConfirmClick}
                    onFormSubmitIntent={onFormSubmitIntent}
                    onRunAction={onRunAction}
                    activeSubmittingActionId={activeSubmittingActionId}
                    setActiveSubmittingActionId={setActiveSubmittingActionId}
                />
            ))}
        </div>
    );
}

type FormActionProps = {
    action: FlatpackListHeaderAction;
    formId: string;
    formProcessing: boolean;
    formIsDirty: boolean;
    formMode: 'create' | 'edit';
    fieldsLength: number;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onFormSubmitIntent: FormActionsProps['onFormSubmitIntent'];
    onFormSubmitConfirmClick: FormActionsProps['onFormSubmitConfirmClick'];
    onRunAction: FormActionsProps['onRunAction'];
    activeSubmittingActionId: string | null;
    setActiveSubmittingActionId: (actionId: string) => void;
};

function FormAction({
    action,
    formId,
    fieldsLength,
    formIsDirty,
    formMode,
    formProcessing,
    isMacPlatform,
    shortcut,
    onFormSubmitConfirmClick,
    onFormSubmitIntent,
    onRunAction,
    activeSubmittingActionId,
    setActiveSubmittingActionId,
}: FormActionProps) {
    const variant = actionVariant(action);
    const iconClass = actionIconButtonClass(action);
    const actionClassName = cn(iconClass, 'h-8 px-3 text-sm sm:h-10 sm:px-4');
    const bodyProps = { action, isMacPlatform, shortcut, variant };
    const visibilityState = actionVisibilityState(action, {
        formIsDirty,
        formMode,
    });
    if (!visibilityState.visible) {
        return null;
    }

    if ('href' in action) {
        const inactiveState = actionEnabledState(action, {
            formIsDirty,
            formMode,
        });
        const disabled = formProcessing || inactiveState.inactive;
        const showInactiveTooltip = inactiveState.inactive && !formProcessing;

        return (
            <ActionInactiveTooltip
                show={showInactiveTooltip}
                message={inactiveState.message}
            >
                {disabled ? (
                    <Button
                        type="button"
                        size="lg"
                        variant={variant}
                        disabled
                        className={actionClassName}
                        data-flatpack-action-id={action.id}
                    >
                        <ActionButtonContent
                            {...bodyProps}
                            showSpinner={false}
                            hideLabelOnMobileWhenIcon
                        />
                    </Button>
                ) : (
                    <Button asChild size="lg" variant={variant}>
                        <Link
                            href={action.href}
                            className={actionClassName}
                            data-flatpack-action-id={action.id}
                        >
                            <ActionButtonContent
                                {...bodyProps}
                                showSpinner={false}
                                hideLabelOnMobileWhenIcon
                            />
                        </Link>
                    </Button>
                )}
            </ActionInactiveTooltip>
        );
    }

    if (!('action' in action) || action.action === '') {
        return null;
    }

    const actionRow = action;
    const isSubmitAction = action.submit === true;
    const inactiveState = actionEnabledState(action, {
        formIsDirty,
        formMode,
    });
    const disabled =
        formProcessing ||
        inactiveState.inactive ||
        (isSubmitAction && fieldsLength === 0) ||
        (!isSubmitAction && formMode !== 'edit');
    const showInactiveTooltip =
        inactiveState.inactive &&
        !formProcessing &&
        (isSubmitAction ? fieldsLength > 0 : true);
    const submitType = action.confirm || !isSubmitAction ? 'button' : 'submit';
    const submitFormId = action.confirm || !isSubmitAction ? undefined : formId;

    return (
        <ActionInactiveTooltip
            show={showInactiveTooltip}
            message={inactiveState.message}
        >
            <Button
                type={submitType}
                form={submitFormId}
                size="lg"
                variant={variant}
                disabled={disabled}
                className={actionClassName}
                data-flatpack-action-id={action.id}
                onClick={
                    action.confirm
                        ? () => {
                              setActiveSubmittingActionId(action.id);
                              if (isSubmitAction) {
                                  onFormSubmitIntent(actionRow);
                              }
                              onFormSubmitConfirmClick(actionRow);
                          }
                        : () => {
                              setActiveSubmittingActionId(action.id);
                              if (isSubmitAction) {
                                  onFormSubmitIntent(actionRow);
                                  return;
                              }
                              onRunAction(actionRow);
                          }
                }
            >
                <ActionButtonContent
                    {...bodyProps}
                    showSpinner={
                        formProcessing && activeSubmittingActionId === action.id
                    }
                    hideLabelOnMobileWhenIcon
                />
            </Button>
        </ActionInactiveTooltip>
    );
}
