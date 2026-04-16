import { Link } from '@inertiajs/react';
import {
    FlatpackActionDirtyTooltip,
    flatpackActionDisabledByDirty,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { LucideIconByName } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type FlatpackListActionsProps = {
    listActions: FlatpackListHeaderAction[];
    onRequestConfirm: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    runListAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void | Promise<void>;
    /** List pages have no form dirty state; default true keeps actions enabled unless global YAML forces disable_until_dirty (then still effectively enabled). */
    formIsDirty?: boolean;
};

export function FlatpackListActions({
    listActions,
    onRequestConfirm,
    runListAction,
    formIsDirty = true,
}: FlatpackListActionsProps) {
    if (listActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
            {listActions.map((action) => {
                if ('href' in action) {
                    const disabledByDirty = flatpackActionDisabledByDirty(
                        action,
                        formIsDirty,
                    );
                    const disabled = disabledByDirty;
                    const showDirtyTooltip = disabledByDirty;

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

                const disabledByDirty = flatpackActionDisabledByDirty(
                    action,
                    formIsDirty,
                );
                const disabled = disabledByDirty;

                return (
                    <FlatpackActionDirtyTooltip
                        key={action.id}
                        show={disabledByDirty}
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
                                    onRequestConfirm(action);
                                    return;
                                }
                                void runListAction(action);
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
