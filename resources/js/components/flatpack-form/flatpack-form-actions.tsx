import { Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    FlatpackActionDirtyTooltip,
    flatpackActionDisabledByDirty,
} from '@/components/flatpack/flatpack-action-dirty-guard';
import { LucideIconByName } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    type RegisteredFlatpackShortcut,
    useRegisterFlatpackShortcuts,
} from '@/contexts/flatpack-shortcuts-registry';
import { useFlatpackActionShortcuts } from '@/hooks/use-flatpack-action-shortcuts';
import {
    formatShortcutHintCompact,
    type ParsedFlatpackShortcut,
} from '@/lib/flatpack-action-shortcuts';
import { cn } from '@/lib/utils';
import type { FlatpackActionVariant } from '@/types/data-table';
import type { FlatpackPageProps } from '@/types/flatpack';
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
    const hideLabelOnMobile = Boolean(action.icon);

    return (
        <>
            {action.icon ? <LucideIconByName name={action.icon} /> : null}
            {showSpinner ? <Spinner className="size-4" /> : null}
            <span className={hideLabelOnMobile ? 'hidden sm:inline' : undefined}>
                {action.label}
            </span>
            {hideLabelOnMobile ? (
                <span className="sr-only">{action.label}</span>
            ) : null}
            <FormActionShortcut
                shortcut={shortcut}
                isMacPlatform={isMacPlatform}
                variant={variant}
            />
        </>
    );
}

type FormSubmitToolbarRow = FlatpackListHeaderAction & { action: string };

type FlatpackFormActionsProps = {
    formActions: FlatpackListHeaderAction[];
    formId: string;
    formProcessing: boolean;
    /** When false and an action has disable_until_dirty, that action stays disabled. Defaults to true (e.g. list pages). */
    formIsDirty?: boolean;
    fieldsLength: number;
    /** Sets which YAML row / handler name is sent on the next {@code POST …/submit}. */
    onFormSubmitIntent: (action: FormSubmitToolbarRow) => void;
    /** Opens confirm dialog for toolbar rows with {@code confirm: true}. */
    onFormSubmitConfirmClick: (action: FormSubmitToolbarRow) => void;
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

    const { shortcutByActionId, isMacPlatform } = useFlatpackActionShortcuts({
        actions: formActions,
    });

    const registeredPageShortcuts =
        useMemo((): RegisteredFlatpackShortcut[] => {
            const rows: RegisteredFlatpackShortcut[] = [];
            for (const action of formActions) {
                const shortcut = shortcutByActionId.get(action.id);
                if (!shortcut) {
                    continue;
                }
                rows.push({
                    id: `action:${action.id}`,
                    description: action.label,
                    shortcut,
                });
            }
            return rows;
        }, [formActions, shortcutByActionId]);

    useRegisterFlatpackShortcuts(
        'flatpack-form-actions',
        registeredPageShortcuts,
    );

    if (formActions.length === 0) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap items-center justify-end gap-2">
            {formActions.map((action) => (
                <FlatpackFormActionRow
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
    isMacPlatform: boolean;
    shortcut?: ParsedFlatpackShortcut;
    onFormSubmitIntent: FlatpackFormActionsProps['onFormSubmitIntent'];
    onFormSubmitConfirmClick: FlatpackFormActionsProps['onFormSubmitConfirmClick'];
};

function FlatpackFormActionRow({
    action,
    formId,
    fieldsLength,
    formIsDirty,
    formProcessing,
    isMacPlatform,
    shortcut,
    onFormSubmitConfirmClick,
    onFormSubmitIntent,
}: FlatpackFormActionRowProps) {
    const variant = actionVariant(action);
    const iconClass = iconButtonClass(action);
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
                        size="sm"
                        variant={variant}
                        disabled
                        className={actionClassName}
                        data-flatpack-action-id={action.id}
                    >
                        <FormActionButtonBody
                            {...bodyProps}
                            showSpinner={false}
                        />
                    </Button>
                ) : (
                    <Button asChild size="sm" variant={variant}>
                        <Link
                            href={action.href}
                            className={actionClassName}
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

    if (!('action' in action) || action.action === '') {
        return null;
    }

    const submitRow = action as FormSubmitToolbarRow;
    const disabledByDirty = flatpackActionDisabledByDirty(action, formIsDirty);
    const disabled = formProcessing || fieldsLength === 0 || disabledByDirty;
    const showDirtyTooltip =
        disabledByDirty && !formProcessing && fieldsLength > 0;

    return (
        <FlatpackActionDirtyTooltip show={showDirtyTooltip}>
            <Button
                type={action.confirm ? 'button' : 'submit'}
                form={action.confirm ? undefined : formId}
                size="sm"
                variant={variant}
                disabled={disabled}
                className={actionClassName}
                data-flatpack-action-id={action.id}
                onClick={
                    action.confirm
                        ? () => {
                              onFormSubmitIntent(submitRow);
                              onFormSubmitConfirmClick(submitRow);
                          }
                        : () => {
                              onFormSubmitIntent(submitRow);
                          }
                }
            >
                <FormActionButtonBody
                    {...bodyProps}
                    showSpinner={formProcessing}
                />
            </Button>
        </FlatpackActionDirtyTooltip>
    );
}
