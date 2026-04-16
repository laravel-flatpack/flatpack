import { Link } from '@inertiajs/react';
import { LucideIconByName } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type FlatpackFormActionsProps = {
    formActions: FlatpackListHeaderAction[];
    formId: string;
    formProcessing: boolean;
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
            {formActions.map((action) =>
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
                ) : action.action === 'save' ? (
                    <Button
                        key={action.id}
                        type={action.confirm ? 'button' : 'submit'}
                        form={action.confirm ? undefined : formId}
                        size="lg"
                        variant={action.variant ?? 'outline'}
                        disabled={formProcessing || fieldsLength === 0}
                        className={cn(
                            action.icon && 'inline-flex items-center gap-1.5',
                        )}
                        onClick={
                            action.confirm ? onSaveConfirmClick : undefined
                        }
                    >
                        {action.icon && <LucideIconByName name={action.icon} />}
                        {formProcessing && <Spinner className="size-4" />}
                        {action.label}
                    </Button>
                ) : (
                    <Button
                        key={action.id}
                        type="button"
                        size="lg"
                        variant={action.variant ?? 'outline'}
                        disabled={
                            formProcessing || record == null || record === ''
                        }
                        className={cn(
                            action.icon && 'inline-flex items-center gap-1.5',
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
                ),
            )}
        </div>
    );
}
