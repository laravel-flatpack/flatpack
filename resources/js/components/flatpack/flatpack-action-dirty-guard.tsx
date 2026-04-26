import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { FlatpackActionCondition } from '@/types/flatpack-actions';

export type FlatpackActionInactiveContext = {
    formIsDirty?: boolean;
    formMode?: 'create' | 'edit' | null;
    listSelectionCount?: number;
    listSearchTerm?: string;
    listFilterState?: Record<string, string | string[] | null> | null;
};

export const FLATPACK_ACTION_INACTIVE_TOOLTIP =
    'This action is not available yet';

function evaluateInactivePredicate(
    predicate: Record<string, unknown>,
    context: FlatpackActionInactiveContext,
): boolean {
    if ('form.dirty' in predicate) {
        return context.formIsDirty === predicate['form.dirty'];
    }
    if ('form.mode_in' in predicate) {
        const modeIn = predicate['form.mode_in'];
        if (!Array.isArray(modeIn) || context.formMode == null) {
            return false;
        }
        return modeIn.includes(context.formMode);
    }
    if ('list.selection.min' in predicate) {
        const min = predicate['list.selection.min'];
        if (typeof min !== 'number') {
            return false;
        }
        return (context.listSelectionCount ?? 0) >= min;
    }
    if ('list.search_present' in predicate) {
        const hasSearch = (context.listSearchTerm ?? '').trim() !== '';
        return hasSearch === predicate['list.search_present'];
    }
    if ('list.filters_applied' in predicate) {
        const state = context.listFilterState ?? {};
        const hasFilters = Object.values(state).some((value) =>
            Array.isArray(value)
                ? value.length > 0
                : value != null && value !== '',
        );
        return hasFilters === predicate['list.filters_applied'];
    }

    return false;
}

function isConditionMet(
    condition: FlatpackActionCondition | undefined,
    context: FlatpackActionInactiveContext,
): boolean {
    if (!condition) {
        return true;
    }
    const all = Array.isArray(condition.all) ? condition.all : [];
    const any = Array.isArray(condition.any) ? condition.any : [];
    const allSatisfied =
        all.length === 0 ||
        all.every((predicate) =>
            evaluateInactivePredicate(
                predicate as unknown as Record<string, unknown>,
                context,
            ),
        );
    const anySatisfied =
        any.length === 0 ||
        any.some((predicate) =>
            evaluateInactivePredicate(
                predicate as unknown as Record<string, unknown>,
                context,
            ),
        );

    return allSatisfied && anySatisfied;
}

export function flatpackActionEnabledState(
    action: { enabled_if?: FlatpackActionCondition },
    context: FlatpackActionInactiveContext,
): { inactive: boolean; message?: string } {
    const enabledIf = action.enabled_if;
    if (!enabledIf) {
        return { inactive: false };
    }
    const isActive = isConditionMet(enabledIf, context);
    if (isActive) {
        return { inactive: false };
    }
    return {
        inactive: true,
        message:
            typeof enabledIf.message === 'string' &&
            enabledIf.message.trim() !== ''
                ? enabledIf.message.trim()
                : FLATPACK_ACTION_INACTIVE_TOOLTIP,
    };
}

export function flatpackActionVisibilityState(
    action: { visible_if?: FlatpackActionCondition },
    context: FlatpackActionInactiveContext,
): { visible: boolean; message?: string } {
    const visibleIf = action.visible_if;
    if (!visibleIf) {
        return { visible: true };
    }
    const visible = isConditionMet(visibleIf, context);

    return {
        visible,
        message:
            typeof visibleIf.message === 'string' &&
            visibleIf.message.trim() !== ''
                ? visibleIf.message.trim()
                : undefined,
    };
}

type FlatpackActionInactiveTooltipProps = {
    show: boolean;
    message?: string;
    children: React.ReactNode;
};

export function FlatpackActionInactiveTooltip({
    show,
    message,
    children,
}: FlatpackActionInactiveTooltipProps) {
    if (!show) {
        return <>{children}</>;
    }

    return (
        <TooltipProvider delayDuration={300}>
            <Tooltip>
                <TooltipTrigger asChild>
                    <span className="inline-flex max-w-full">{children}</span>
                </TooltipTrigger>
                <TooltipContent side="bottom" sideOffset={4}>
                    {message ?? FLATPACK_ACTION_INACTIVE_TOOLTIP}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
