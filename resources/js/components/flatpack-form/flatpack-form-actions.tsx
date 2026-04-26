import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    FlatpackActionButtonContent,
    flatpackActionIconButtonClass,
    flatpackActionVariant,
} from '@/components/flatpack/flatpack-action-button-content';
import {
    FlatpackActionDirtyTooltip,
    flatpackActionDisabledByDirty,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { Button } from '@/components/ui/button';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import { useFlatpackRegisterActionShortcuts } from '@/hooks/use-flatpack-register-action-shortcuts';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';
import type { FlatpackListSubmitAction } from '@/types/flatpack-actions';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type FlatpackFormActionsProps = {
    formActions: FlatpackListHeaderAction[];
    formId: string;
    formProcessing: boolean;
    /** When false and an action has disable_until_dirty, that action stays disabled. Defaults to true (e.g. list pages). */
    formIsDirty?: boolean;
    fieldsLength: number;
    /** Sets which YAML row / handler name is sent on the next {@code POST …/submit}. */
    onFormSubmitIntent: (action: FlatpackListSubmitAction) => void;
    /** Opens confirm dialog for toolbar rows with {@code confirm: true}. */
    onFormSubmitConfirmClick: (action: FlatpackListSubmitAction) => void;
};

export function FlatpackFormActions({
    formActions,
    formId,
    formProcessing,
    formIsDirty = true,
    fieldsLength,
    onFormSubmitIntent,
    onFormSubmitConfirmClick,
}: FlatpackFormActionsProps) {
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
        scope: 'flatpack-form-actions',
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
                <FlatpackFormAction
                    key={action.id}
                    action={action}
                    formId={formId}
                    fieldsLength={fieldsLength}
                    formIsDirty={formIsDirty ?? true}
                    formProcessing={formProcessing}
                    isMacPlatform={isMacPlatform}
                    shortcut={
                        showShortcutHintsOnButtons
                            ? shortcutByActionId.get(action.id)
                            : undefined
                    }
                    onFormSubmitConfirmClick={onFormSubmitConfirmClick}
                    onFormSubmitIntent={onFormSubmitIntent}
                    activeSubmittingActionId={activeSubmittingActionId}
                    setActiveSubmittingActionId={setActiveSubmittingActionId}
                />
            ))}
        </div>
    );
}

type FlatpackFormActionProps = {
    action: FlatpackListHeaderAction;
    formId: string;
    formProcessing: boolean;
    formIsDirty: boolean;
    fieldsLength: number;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onFormSubmitIntent: FlatpackFormActionsProps['onFormSubmitIntent'];
    onFormSubmitConfirmClick: FlatpackFormActionsProps['onFormSubmitConfirmClick'];
    activeSubmittingActionId: string | null;
    setActiveSubmittingActionId: (actionId: string) => void;
};

function FlatpackFormAction({
    action,
    formId,
    fieldsLength,
    formIsDirty,
    formProcessing,
    isMacPlatform,
    shortcut,
    onFormSubmitConfirmClick,
    onFormSubmitIntent,
    activeSubmittingActionId,
    setActiveSubmittingActionId,
}: FlatpackFormActionProps) {
    const variant = flatpackActionVariant(action);
    const iconClass = flatpackActionIconButtonClass(action);
    const actionClassName = cn(iconClass, 'h-8 px-3 text-sm sm:h-10 sm:px-4');
    const bodyProps = { action, isMacPlatform, shortcut, variant };

    if ('href' in action) {
        const disabledByDirty = flatpackActionDisabledByDirty(
            action,
            formIsDirty,
        );
        const disabled = formProcessing || disabledByDirty;
        const showDirtyTooltip = disabledByDirty && !formProcessing;

        return (
            <FlatpackActionDirtyTooltip show={showDirtyTooltip}>
                {disabled ? (
                    <Button
                        type="button"
                        size="lg"
                        variant={variant}
                        disabled
                        className={actionClassName}
                        data-flatpack-action-id={action.id}
                    >
                        <FlatpackActionButtonContent
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
                            <FlatpackActionButtonContent
                                {...bodyProps}
                                showSpinner={false}
                                hideLabelOnMobileWhenIcon
                            />
                        </Link>
                    </Button>
                )}
            </FlatpackActionDirtyTooltip>
        );
    }

    if (!('action' in action) || action.action === '') {
        return null;
    }

    const submitRow = action;
    const disabledByDirty = flatpackActionDisabledByDirty(action, formIsDirty);
    const disabled = formProcessing || fieldsLength === 0 || disabledByDirty;
    const showDirtyTooltip =
        disabledByDirty && !formProcessing && fieldsLength > 0;

    return (
        <FlatpackActionDirtyTooltip show={showDirtyTooltip}>
            <Button
                type={action.confirm ? 'button' : 'submit'}
                form={action.confirm ? undefined : formId}
                size="lg"
                variant={variant}
                disabled={disabled}
                className={actionClassName}
                data-flatpack-action-id={action.id}
                onClick={
                    action.confirm
                        ? () => {
                              setActiveSubmittingActionId(action.id);
                              onFormSubmitIntent(submitRow);
                              onFormSubmitConfirmClick(submitRow);
                          }
                        : () => {
                              setActiveSubmittingActionId(action.id);
                              onFormSubmitIntent(submitRow);
                          }
                }
            >
                <FlatpackActionButtonContent
                    {...bodyProps}
                    showSpinner={
                        formProcessing && activeSubmittingActionId === action.id
                    }
                    hideLabelOnMobileWhenIcon
                />
            </Button>
        </FlatpackActionDirtyTooltip>
    );
}
