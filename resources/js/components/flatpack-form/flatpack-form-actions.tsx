import { Link } from '@inertiajs/react';
import {
    FlatpackActionDirtyTooltip,
    flatpackActionDisabledByDirty,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { LucideIconByName } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type FlatpackFormActionsProps = {
    formActions: FlatpackListHeaderAction[];
    formId: string;
    formProcessing: boolean;
    /** When false and an action has disable_until_dirty, that action stays disabled. Defaults to true (e.g. list pages). */
    formIsDirty?: boolean;
    fieldsLength: number;
    record: string | null;
    onSaveConfirmClick: () => void;
    onNamedActionConfirm: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    runNamedAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void | Promise<void>;
};

export function FlatpackFormActions({
    formActions,
    formId,
    formProcessing,
    formIsDirty = true,
    fieldsLength,
    record,
    onSaveConfirmClick,
    onNamedActionConfirm,
    runNamedAction,
}: FlatpackFormActionsProps) {
    if (formActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
            {formActions.map((action) => {
                if ('href' in action) {
                    const disabledByDirty = flatpackActionDisabledByDirty(
                        action,
                        formIsDirty,
                    );
                    const disabled = formProcessing || disabledByDirty;
                    const showDirtyTooltip = disabledByDirty && !formProcessing;

                    const labelAndIcon = (
                        <>
                            {action.icon ? (
                                <LucideIconByName name={action.icon} />
                            ) : null}
                            {action.label}
                        </>
                    );

                    return (
                        <FlatpackActionDirtyTooltip
                            key={action.id}
                            show={showDirtyTooltip}
                        >
                            {disabled ? (
                                <Button
                                    type="button"
                                    size="lg"
                                    variant={action.variant ?? 'outline'}
                                    disabled
                                    className={cn(
                                        action.icon &&
                                            'inline-flex items-center gap-1.5',
                                    )}
                                >
                                    {labelAndIcon}
                                </Button>
                            ) : (
                                <Button
                                    asChild
                                    size="lg"
                                    variant={action.variant ?? 'outline'}
                                >
                                    <Link
                                        href={action.href}
                                        className={cn(
                                            action.icon &&
                                                'inline-flex items-center gap-1.5',
                                        )}
                                    >
                                        {labelAndIcon}
                                    </Link>
                                </Button>
                            )}
                        </FlatpackActionDirtyTooltip>
                    );
                }

                if (action.action === 'save') {
                    const disabledByDirty = flatpackActionDisabledByDirty(
                        action,
                        formIsDirty,
                    );
                    const disabled =
                        formProcessing || fieldsLength === 0 || disabledByDirty;
                    const showDirtyTooltip =
                        disabledByDirty && !formProcessing && fieldsLength > 0;

                    return (
                        <FlatpackActionDirtyTooltip
                            key={action.id}
                            show={showDirtyTooltip}
                        >
                            <Button
                                type={action.confirm ? 'button' : 'submit'}
                                form={action.confirm ? undefined : formId}
                                size="lg"
                                variant={action.variant ?? 'outline'}
                                disabled={disabled}
                                className={cn(
                                    action.icon &&
                                        'inline-flex items-center gap-1.5',
                                )}
                                onClick={
                                    action.confirm
                                        ? onSaveConfirmClick
                                        : undefined
                                }
                            >
                                {action.icon && (
                                    <LucideIconByName name={action.icon} />
                                )}
                                {formProcessing && (
                                    <Spinner className="size-4" />
                                )}
                                {action.label}
                            </Button>
                        </FlatpackActionDirtyTooltip>
                    );
                }

                const disabledByDirty = flatpackActionDisabledByDirty(
                    action,
                    formIsDirty,
                );
                const disabled =
                    formProcessing ||
                    record == null ||
                    record === '' ||
                    disabledByDirty;
                const showDirtyTooltip =
                    disabledByDirty &&
                    !formProcessing &&
                    record != null &&
                    record !== '';

                return (
                    <FlatpackActionDirtyTooltip
                        key={action.id}
                        show={showDirtyTooltip}
                    >
                        <Button
                            type="button"
                            size="lg"
                            variant={action.variant ?? 'outline'}
                            disabled={disabled}
                            className={cn(
                                action.icon &&
                                    'inline-flex items-center gap-1.5',
                            )}
                            onClick={() => {
                                if (action.confirm) {
                                    onNamedActionConfirm(action);
                                    return;
                                }
                                void runNamedAction(action);
                            }}
                        >
                            {action.icon ? (
                                <LucideIconByName name={action.icon} />
                            ) : null}
                            {action.label}
                        </Button>
                    </FlatpackActionDirtyTooltip>
                );
            })}
        </div>
    );
}
