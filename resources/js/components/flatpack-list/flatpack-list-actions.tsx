import { Link, usePage } from '@inertiajs/react';
import {
    FlatpackActionButtonContent,
    flatpackActionIconButtonClass,
    flatpackActionVariant,
} from '@/components/flatpack/flatpack-action-button-content';
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
    /** List pages have no form dirty state; default true keeps actions enabled unless global YAML forces disable_until_dirty (then still effectively enabled). */
    formIsDirty?: boolean;
};

export function FlatpackListActions({
    listActions,
    onRequestConfirm,
    runListAction,
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
};

function FlatpackListAction({
    action,
    isMacPlatform,
    shortcut,
    onRequestConfirm,
    runListAction,
}: FlatpackListActionProps) {
    const variant = flatpackActionVariant(action);
    const iconClass = flatpackActionIconButtonClass(action);
    const actionClassName = cn(iconClass);
    const bodyProps = { action, isMacPlatform, shortcut, variant };

    if ('href' in action) {
        return (
            <Button asChild size="lg" variant={variant}>
                <Link
                    href={action.href}
                    className={actionClassName}
                    data-flatpack-action-id={action.id}
                >
                    <FlatpackActionButtonContent {...bodyProps} />
                </Link>
            </Button>
        );
    }

    return (
        <Button
            type="button"
            size="lg"
            variant={variant}
            className={actionClassName}
            data-flatpack-action-id={action.id}
            onClick={() => {
                if (action.confirm) {
                    onRequestConfirm(action);
                    return;
                }

                void runListAction(action);
            }}
        >
            <FlatpackActionButtonContent {...bodyProps} />
        </Button>
    );
}
