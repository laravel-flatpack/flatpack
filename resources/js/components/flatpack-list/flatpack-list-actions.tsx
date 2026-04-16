import { Link } from '@inertiajs/react';
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
};

export function FlatpackListActions({
    listActions,
    onRequestConfirm,
    runListAction,
}: FlatpackListActionsProps) {
    if (listActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
            {listActions.map((action) =>
                'href' in action ? (
                    <Button
                        key={action.id}
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
                            {action.icon ? (
                                <LucideIconByName name={action.icon} />
                            ) : null}
                            {action.label}
                        </Link>
                    </Button>
                ) : (
                    <Button
                        key={action.id}
                        type="button"
                        size="lg"
                        variant={action.variant ?? 'outline'}
                        className={cn(
                            action.icon && 'inline-flex items-center gap-1.5',
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
                ),
            )}
        </div>
    );
}
