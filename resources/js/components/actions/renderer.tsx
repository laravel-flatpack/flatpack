import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ActionButton } from '@/components/actions/action-button';
import {
    actionEnabledState,
    actionVisibilityState,
} from '@/components/actions/action-dirty-guard';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import { useFlatpackRegisterActionShortcuts } from '@/hooks/use-flatpack-register-action-shortcuts';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';
import type { FlatpackListSubmitAction } from '@/types/flatpack-actions';
import type { ToolbarFieldAlign } from '@/types/form-fields';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

export type FormActionsRendererProps = {
    actions: FlatpackListHeaderAction[];
    formId: string;
    formProcessing: boolean;
    formIsDirty?: boolean;
    formMode: 'create' | 'edit';
    fieldsLength: number;
    /** Live form attribute values for `form.field_*` action predicates. */
    formValues?: Record<string, unknown>;
    onFormSubmitIntent: (action: FlatpackListSubmitAction) => void;
    onFormSubmitConfirmClick: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    onRunAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    /** Default matches header toolbar ({@code justify-end}). */
    align?: ToolbarFieldAlign;
    /** When set, registers keyboard shortcuts for this action list. */
    shortcutRegistration?: {
        scope: string;
        actions: FlatpackListHeaderAction[];
    };
};

function justifyClass(align: ToolbarFieldAlign | undefined): string {
    switch (align ?? 'right') {
        case 'left':
        case 'start':
            return 'justify-start';
        case 'center':
            return 'justify-center';
        case 'right':
        case 'end':
            return 'justify-end';
        case 'spaced':
            return 'justify-between w-full';
        default:
            return 'justify-end';
    }
}

export function FormActionsRenderer({
    actions,
    formId,
    formProcessing,
    formIsDirty = true,
    formMode,
    fieldsLength,
    formValues,
    onFormSubmitIntent,
    onFormSubmitConfirmClick,
    onRunAction,
    align = 'right',
    shortcutRegistration,
}: FormActionsRendererProps) {
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();

    const showShortcutHintsOnButtons =
        flatpack.showActionShortcutHints === true;

    const [activeSubmittingActionId, setActiveSubmittingActionId] = useState<
        string | null
    >(null);

    const shortcutActions =
        shortcutRegistration?.actions ?? ([] as FlatpackListHeaderAction[]);

    const { shortcutByActionId, isMacPlatform } = useFlatpackActionShortcuts({
        actions: shortcutRegistration !== undefined ? shortcutActions : [],
    });

    useFlatpackRegisterActionShortcuts({
        scope: shortcutRegistration?.scope ?? '__flatpack_inline_actions_none',
        actions: shortcutRegistration !== undefined ? shortcutActions : [],
        shortcutByActionId,
    });

    useEffect(() => {
        if (!formProcessing) {
            setActiveSubmittingActionId(null);
        }
    }, [formProcessing]);

    if (actions.length === 0) {
        return null;
    }

    const hints = showShortcutHintsOnButtons ? shortcutByActionId : undefined;

    return (
        <div
            className={cn(
                'flex shrink-0 flex-wrap items-center gap-2',
                justifyClass(align),
            )}
        >
            {actions.map((action) => (
                <FormActionsRendererRow
                    key={action.id}
                    action={action}
                    formId={formId}
                    fieldsLength={fieldsLength}
                    formValues={formValues}
                    formIsDirty={formIsDirty ?? true}
                    formMode={formMode}
                    formProcessing={formProcessing}
                    isMacPlatform={isMacPlatform}
                    shortcut={
                        hints !== undefined ? hints.get(action.id) : undefined
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

type FormActionsRendererRowProps = {
    action: FlatpackListHeaderAction;
    formId: string;
    formProcessing: boolean;
    formIsDirty: boolean;
    formMode: 'create' | 'edit';
    fieldsLength: number;
    formValues?: Record<string, unknown>;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onFormSubmitIntent: FormActionsRendererProps['onFormSubmitIntent'];
    onFormSubmitConfirmClick: FormActionsRendererProps['onFormSubmitConfirmClick'];
    onRunAction: FormActionsRendererProps['onRunAction'];
    activeSubmittingActionId: string | null;
    setActiveSubmittingActionId: (actionId: string) => void;
};

function FormActionsRendererRow({
    action,
    formId,
    fieldsLength,
    formValues,
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
}: FormActionsRendererRowProps) {
    const actionClassName = 'h-8 px-3 text-sm sm:h-10 sm:px-4';
    const visibilityState = actionVisibilityState(action, {
        formIsDirty,
        formMode,
        formValues,
    });
    if (!visibilityState.visible) {
        return null;
    }

    if ('href' in action) {
        const inactiveState = actionEnabledState(action, {
            formIsDirty,
            formMode,
            formValues,
        });
        const disabled = formProcessing || inactiveState.inactive;

        return (
            <ActionButton
                action={action}
                isMacPlatform={isMacPlatform}
                shortcut={shortcut}
                size="lg"
                className={actionClassName}
                disabled={disabled}
                href={action.href}
                showSpinner={false}
                hideLabelOnMobileWhenIcon
                data-flatpack-action-id={action.id}
            />
        );
    }

    if (!('action' in action) || action.action === '') {
        return null;
    }

    const actionRow = action;
    const isSubmitAction = action.submit === true;
    const handlerMissing = action.handler_missing === true;
    const inactiveState = actionEnabledState(action, {
        formIsDirty,
        formMode,
        formValues,
    });
    const disabled =
        handlerMissing ||
        formProcessing ||
        inactiveState.inactive ||
        (isSubmitAction && fieldsLength === 0) ||
        (!isSubmitAction && formMode !== 'edit');
    const submitType = action.confirm || !isSubmitAction ? 'button' : 'submit';
    const submitFormId = action.confirm || !isSubmitAction ? undefined : formId;

    return (
        <ActionButton
            action={action}
            isMacPlatform={isMacPlatform}
            shortcut={shortcut}
            size="lg"
            className={actionClassName}
            nativeType={submitType}
            form={submitFormId}
            disabled={disabled}
            onClick={
                handlerMissing
                    ? undefined
                    : action.confirm
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
            showSpinner={
                formProcessing && activeSubmittingActionId === action.id
            }
            hideLabelOnMobileWhenIcon
            data-flatpack-action-id={action.id}
        />
    );
}
