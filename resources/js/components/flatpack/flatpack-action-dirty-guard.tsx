import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export const FLATPACK_ACTION_DISABLED_UNTIL_DIRTY_TOOLTIP =
    'No changes to save';

export function flatpackActionDisabledByDirty(
    action: { disable_until_dirty?: boolean },
    formIsDirty: boolean,
): boolean {
    return action.disable_until_dirty === true && !formIsDirty;
}

type FlatpackActionDirtyTooltipProps = {
    show: boolean;
    children: React.ReactNode;
};

export function FlatpackActionDirtyTooltip({
    show,
    children,
}: FlatpackActionDirtyTooltipProps) {
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
                    {FLATPACK_ACTION_DISABLED_UNTIL_DIRTY_TOOLTIP}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
