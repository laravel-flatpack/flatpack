import { usePage } from '@inertiajs/react';
import { ActionButton } from '@/components/actions/action-button';
import {
    ActionInactiveTooltip,
    actionEnabledState,
    actionVisibilityState,
} from '@/components/actions/action-dirty-guard';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import { useFlatpackRegisterActionShortcuts } from '@/hooks/use-flatpack-register-action-shortcuts';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';
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
                <ActionButton
                    action={action}
                    isMacPlatform={isMacPlatform}
                    shortcut={shortcut}
                    size="lg"
                    disabled={inactiveState.inactive}
                    href={action.href}
                    showSpinner={false}
                    hideLabelOnMobileWhenIcon
                    data-flatpack-action-id={action.id}
                />
            </ActionInactiveTooltip>
        );
    }

    return (
        <ActionInactiveTooltip
            show={inactiveState.inactive}
            message={inactiveState.message}
        >
            <ActionButton
                action={action}
                isMacPlatform={isMacPlatform}
                shortcut={shortcut}
                size="lg"
                disabled={inactiveState.inactive}
                showSpinner={false}
                hideLabelOnMobileWhenIcon
                data-flatpack-action-id={action.id}
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
            />
        </ActionInactiveTooltip>
    );
}
