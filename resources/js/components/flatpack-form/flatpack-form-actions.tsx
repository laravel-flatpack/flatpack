import { Link } from '@inertiajs/react';
import {
    FlatpackActionDirtyTooltip,
    flatpackActionDisabledByDirty,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { LucideIconByName } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import {
    formatShortcutHintCompact,
    type ParsedFlatpackShortcut,
} from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type { FlatpackActionVariant } from '@/types/data-table';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

const VARIANT_FALLBACK: FlatpackActionVariant = 'outline';

const SHORTCUT_CHIP_BASE =
    'ml-2 inline-flex max-w-full shrink-0 items-center rounded px-2 py-0.5 text-[0.6875rem] font-medium leading-none tracking-tight tabular-nums select-none group-disabled/button:opacity-50';

const SHORTCUT_CHIP_BY_VARIANT: Record<FlatpackActionVariant, string> = {
    default: 'bg-primary-foreground/15 text-primary-foreground',
    secondary: 'bg-secondary-foreground/12 text-secondary-foreground',
    destructive: 'bg-black/50 text-white dark:bg-black/30 dark:text-white',
    ghost: 'bg-muted text-muted-foreground shadow-none',
    link: 'bg-muted/80 text-muted-foreground shadow-none',
    outline:
        'bg-neutral-900 text-neutral-50 dark:border-white/15 dark:bg-neutral-800 dark:text-neutral-100',
};

function shortcutChipClass(variant: FlatpackActionVariant): string {
    return cn(SHORTCUT_CHIP_BASE, SHORTCUT_CHIP_BY_VARIANT[variant]);
}

function actionVariant(
    action: FlatpackListHeaderAction,
): FlatpackActionVariant {
    return action.variant ?? VARIANT_FALLBACK;
}

function iconButtonClass(action: FlatpackListHeaderAction): string | undefined {
    return action.icon ? 'inline-flex items-center gap-1.5' : undefined;
}

function FormActionShortcut({
    shortcut,
    isMacPlatform,
    variant,
}: {
    shortcut?: ParsedFlatpackShortcut;
    isMacPlatform: boolean;
    variant: FlatpackActionVariant;
}) {
    if (!shortcut) {
        return null;
    }
    return (
        <span className={shortcutChipClass(variant)} aria-hidden>
            {formatShortcutHintCompact(shortcut, isMacPlatform)}
        </span>
    );
}

/** Icon, optional submit spinner, label, and shortcut chip — shared by every form action button. */
function FormActionButtonBody({
    action,
    isMacPlatform,
    shortcut,
    showSpinner,
    variant,
}: {
    action: FlatpackListHeaderAction;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    showSpinner: boolean;
    variant: FlatpackActionVariant;
}) {
    return (
        <>
            {action.icon ? <LucideIconByName name={action.icon} /> : null}
            {showSpinner ? <Spinner className="size-4" /> : null}
            {action.label}
            <FormActionShortcut
                shortcut={shortcut}
                isMacPlatform={isMacPlatform}
                variant={variant}
            />
        </>
    );
}

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
    const { shortcutByActionId, isMacPlatform } = useFlatpackActionShortcuts({
        actions: formActions,
    });

    if (formActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end">
            {formActions.map((action) => (
                <FlatpackFormActionRow
                    key={action.id}
                    action={action}
                    formId={formId}
                    fieldsLength={fieldsLength}
                    formIsDirty={formIsDirty ?? true}
                    formProcessing={formProcessing}
                    isMacPlatform={isMacPlatform}
                    record={record}
                    shortcut={shortcutByActionId?.get(action.id)}
                    onNamedActionConfirm={onNamedActionConfirm}
                    onSaveConfirmClick={onSaveConfirmClick}
                    runNamedAction={runNamedAction}
                />
            ))}
        </div>
    );
}

type FlatpackFormActionRowProps = {
    action: FlatpackListHeaderAction;
    formId: string;
    formProcessing: boolean;
    formIsDirty: boolean;
    fieldsLength: number;
    record: string | null;
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onSaveConfirmClick: FlatpackFormActionsProps['onSaveConfirmClick'];
    onNamedActionConfirm: FlatpackFormActionsProps['onNamedActionConfirm'];
    runNamedAction: FlatpackFormActionsProps['runNamedAction'];
};

function FlatpackFormActionRow({
    action,
    formId,
    fieldsLength,
    formIsDirty,
    formProcessing,
    isMacPlatform,
    record,
    shortcut,
    onNamedActionConfirm,
    onSaveConfirmClick,
    runNamedAction,
}: FlatpackFormActionRowProps) {
    const variant = actionVariant(action);
    const iconClass = iconButtonClass(action);
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
                        className={iconClass}
                        data-flatpack-action-id={action.id}
                    >
                        <FormActionButtonBody
                            {...bodyProps}
                            showSpinner={false}
                        />
                    </Button>
                ) : (
                    <Button asChild size="lg" variant={variant}>
                        <Link
                            href={action.href}
                            className={iconClass}
                            data-flatpack-action-id={action.id}
                        >
                            <FormActionButtonBody
                                {...bodyProps}
                                showSpinner={false}
                            />
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
            <FlatpackActionDirtyTooltip show={showDirtyTooltip}>
                <Button
                    type={action.confirm ? 'button' : 'submit'}
                    form={action.confirm ? undefined : formId}
                    size="lg"
                    variant={variant}
                    disabled={disabled}
                    className={iconClass}
                    data-flatpack-action-id={action.id}
                    onClick={action.confirm ? onSaveConfirmClick : undefined}
                >
                    <FormActionButtonBody
                        {...bodyProps}
                        showSpinner={formProcessing}
                    />
                </Button>
            </FlatpackActionDirtyTooltip>
        );
    }

    const disabledByDirty = flatpackActionDisabledByDirty(action, formIsDirty);
    const disabled =
        formProcessing || record == null || record === '' || disabledByDirty;
    const showDirtyTooltip =
        disabledByDirty && !formProcessing && record != null && record !== '';

    return (
        <FlatpackActionDirtyTooltip show={showDirtyTooltip}>
            <Button
                type="button"
                size="lg"
                variant={variant}
                disabled={disabled}
                className={iconClass}
                data-flatpack-action-id={action.id}
                onClick={() => {
                    if (action.confirm) {
                        onNamedActionConfirm(action);
                        return;
                    }
                    void runNamedAction(action);
                }}
            >
                <FormActionButtonBody {...bodyProps} showSpinner={false} />
            </Button>
        </FlatpackActionDirtyTooltip>
    );
}
