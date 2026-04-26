import { Link, usePage } from '@inertiajs/react';
import {
    FlatpackActionButtonContent,
    flatpackActionIconButtonClass,
    flatpackActionVariant,
} from '@/components/flatpack/flatpack-action-button-content';
import {
    FlatpackActionInactiveTooltip,
    flatpackActionEnabledState,
    flatpackActionVisibilityState,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { Button } from '@/components/ui/button';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import { useFlatpackRegisterActionShortcuts } from '@/hooks/use-flatpack-register-action-shortcuts';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type { FlatpackPageProps } from '@/types/flatpack';
import type { FlatpackListSubmitAction } from '@/types/flatpack-actions';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type FlatpackListActionsProps = {
    listActions: FlatpackListHeaderAction[];
    onRequestConfirm: (action: FlatpackListSubmitAction) => void;
    runListAction: (action: FlatpackListSubmitAction) => void | Promise<void>;
    searchTerm?: string;
    serverFilterState?: Record<string, string | string[] | null>;
};

export function FlatpackListActions({
    listActions,
    onRequestConfirm,
    runListAction,
    searchTerm = '',
    serverFilterState = {},
}: FlatpackListActionsProps) {
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();

    const showShortcutHintsOnButtons =
        flatpack.showActionShortcutHints === true;

    const { shortcutByActionId, isMacPlatform } = useFlatpackActionShortcuts({
        actions: listActions,
    });

    useFlatpackRegisterActionShortcuts({
        scope: 'flatpack-list-actions',
        actions: listActions,
        shortcutByActionId,
    });

    if (listActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
            {listActions.map((action) => (
                <FlatpackListAction
                    key={action.id}
                    action={action}
                    isMacPlatform={isMacPlatform}
                    shortcut={
                        showShortcutHintsOnButtons
                            ? shortcutByActionId.get(action.id)
                            : undefined
                    }
                    onRequestConfirm={onRequestConfirm}
                    runListAction={runListAction}
                    searchTerm={searchTerm}
                    serverFilterState={serverFilterState}
                />
            ))}
        </div>
    );
}

type FlatpackListActionProps = {
    action: FlatpackListHeaderAction;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onRequestConfirm: FlatpackListActionsProps['onRequestConfirm'];
    runListAction: FlatpackListActionsProps['runListAction'];
    searchTerm: string;
    serverFilterState: Record<string, string | string[] | null>;
};

function FlatpackListAction({
    action,
    isMacPlatform,
    shortcut,
    onRequestConfirm,
    runListAction,
    searchTerm,
    serverFilterState,
}: FlatpackListActionProps) {
    const variant = flatpackActionVariant(action);
    const iconClass = flatpackActionIconButtonClass(action);
    const actionClassName = cn(iconClass);
    const bodyProps = { action, isMacPlatform, shortcut, variant };
    const visibilityState = flatpackActionVisibilityState(action, {
        listSearchTerm: searchTerm,
        listFilterState: serverFilterState,
    });
    if (!visibilityState.visible) {
        return null;
    }
    const inactiveState = flatpackActionEnabledState(action, {
        listSearchTerm: searchTerm,
        listFilterState: serverFilterState,
    });

    if ('href' in action) {
        return (
            <FlatpackActionInactiveTooltip
                show={inactiveState.inactive}
                message={inactiveState.message}
            >
                {inactiveState.inactive ? (
                    <Button type="button" size="lg" variant={variant} disabled>
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
            </FlatpackActionInactiveTooltip>
        );
    }

    return (
        <FlatpackActionInactiveTooltip
            show={inactiveState.inactive}
            message={inactiveState.message}
        >
            <Button
                type="button"
                size="lg"
                variant={variant}
                className={actionClassName}
                data-flatpack-action-id={action.id}
                disabled={inactiveState.inactive}
                onClick={() => {
                    if (inactiveState.inactive) {
                        return;
                    }
                    if (action.confirm) {
                        onRequestConfirm(action);
                        return;
                    }

                    void runListAction(action);
                }}
            >
                <FlatpackActionButtonContent
                    {...bodyProps}
                    showSpinner={false}
                    hideLabelOnMobileWhenIcon
                />
            </Button>
        </FlatpackActionInactiveTooltip>
    );
}
