import { Link, usePage } from '@inertiajs/react';
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

type ListActionsProps = {
    listActions: FlatpackListHeaderAction[];
    onRequestConfirm: (action: FlatpackListSubmitAction) => void;
    runListAction: (action: FlatpackListSubmitAction) => void | Promise<void>;
    searchTerm?: string;
    serverFilterState?: Record<string, string | string[] | null>;
};

export function ListActions({
    listActions,
    onRequestConfirm,
    runListAction,
    searchTerm = '',
    serverFilterState = {},
}: ListActionsProps) {
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();

    const showShortcutHintsOnButtons =
        flatpack.showActionShortcutHints === true;

    const { shortcutByActionId, isMacPlatform } = useFlatpackActionShortcuts({
        actions: listActions,
    });

    useFlatpackRegisterActionShortcuts({
        scope: 'list-actions',
        actions: listActions,
        shortcutByActionId,
    });

    if (listActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
            {listActions.map((action) => (
                <ListAction
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

type ListActionProps = {
    action: FlatpackListHeaderAction;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onRequestConfirm: ListActionsProps['onRequestConfirm'];
    runListAction: ListActionsProps['runListAction'];
    searchTerm: string;
    serverFilterState: Record<string, string | string[] | null>;
};

function ListAction({
    action,
    isMacPlatform,
    shortcut,
    onRequestConfirm,
    runListAction,
    searchTerm,
    serverFilterState,
}: ListActionProps) {
    const variant = actionVariant(action);
    const iconClass = actionIconButtonClass(action);
    const actionClassName = cn(iconClass);
    const bodyProps = { action, isMacPlatform, shortcut, variant };
    const visibilityState = actionVisibilityState(action, {
        listSearchTerm: searchTerm,
        listFilterState: serverFilterState,
    });
    if (!visibilityState.visible) {
        return null;
    }
    const inactiveState = actionEnabledState(action, {
        listSearchTerm: searchTerm,
        listFilterState: serverFilterState,
    });

    if ('href' in action) {
        return (
            <ActionInactiveTooltip
                show={inactiveState.inactive}
                message={inactiveState.message}
            >
                {inactiveState.inactive ? (
                    <Button type="button" size="lg" variant={variant} disabled>
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

    return (
        <ActionInactiveTooltip
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
                <ActionButtonContent
                    {...bodyProps}
                    showSpinner={false}
                    hideLabelOnMobileWhenIcon
                />
            </Button>
        </ActionInactiveTooltip>
    );
}
