import { Link } from '@inertiajs/react';
import {
    CopyIcon,
    EllipsisVerticalIcon,
    type LucideIcon,
    PencilIcon,
    StarIcon,
    Trash2Icon,
} from 'lucide-react';
import { truncateActionMenuLabel } from '@/components/table/data-table-constants';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { interpolateRowPlaceholders } from '@/lib/data-table-utils';
import { cn } from '@/lib/utils';
import type { FlatpackDataTableActionButton } from '@/types/data-table';

function iconForAction(iconOrKey?: string): LucideIcon | null {
    const k = iconOrKey?.toLowerCase() ?? '';
    if (k === 'edit' || k === 'pencil') {
        return PencilIcon;
    }
    if (k === 'copy' || k === 'duplicate' || k === 'clone') {
        return CopyIcon;
    }
    if (k === 'star' || k === 'favorite' || k === 'favourite') {
        return StarIcon;
    }
    if (k === 'delete' || k === 'trash' || k === 'remove') {
        return Trash2Icon;
    }
    return null;
}

function actionIsDestructive(
    actionKey: string,
    cfg: FlatpackDataTableActionButton,
): boolean {
    const a = cfg.action?.toLowerCase();
    return (
        actionKey.toLowerCase() === 'delete' ||
        cfg.icon?.toLowerCase() === 'delete' ||
        a === 'delete' ||
        a === 'destroy' ||
        a === 'remove'
    );
}

export function DataTableActionsCell({
    buttons,
    row,
}: {
    buttons: Record<string, FlatpackDataTableActionButton>;
    row: Record<string, unknown>;
}) {
    const entries = Object.entries(buttons);
    const primary = entries.filter(
        ([key, cfg]) => !actionIsDestructive(key, cfg),
    );
    const destructive = entries.filter(([key, cfg]) =>
        actionIsDestructive(key, cfg),
    );

    const actionRowLabel = (
        cfg: FlatpackDataTableActionButton,
        actionKey: string,
    ) => {
        const Icon = iconForAction(cfg.icon) ?? iconForAction(actionKey);
        const displayLabel = truncateActionMenuLabel(cfg.label);
        const charTruncated = displayLabel !== cfg.label;
        return (
            <span className="flex items-center gap-2">
                {Icon ? (
                    <Icon
                        className="size-3.5 shrink-0 opacity-70"
                        aria-hidden
                    />
                ) : null}
                <span
                    className={cn(
                        'whitespace-nowrap',
                        charTruncated && 'min-w-0 max-w-full truncate',
                    )}
                    title={charTruncated ? cfg.label : undefined}
                >
                    {displayLabel}
                </span>
            </span>
        );
    };

    return (
        <div className="flex justify-end">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8 text-muted-foreground data-[state=open]:bg-muted"
                    >
                        <EllipsisVerticalIcon className="size-4" />
                        <span className="sr-only">Open row actions</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="min-w-44 max-w-xs">
                    {primary.map(([actionKey, cfg]) => {
                        const template = cfg.href ?? cfg.url ?? '';
                        const resolved = template
                            ? interpolateRowPlaceholders(template, row)
                            : '';
                        const label = actionRowLabel(cfg, actionKey);

                        if (resolved) {
                            const external = /^https?:\/\//i.test(resolved);
                            return (
                                <DropdownMenuItem
                                    key={actionKey}
                                    asChild
                                    {...(cfg.action
                                        ? {
                                              'data-flatpack-action':
                                                  cfg.action,
                                          }
                                        : {})}
                                >
                                    {external ? (
                                        <a
                                            href={resolved}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {label}
                                        </a>
                                    ) : (
                                        <Link href={resolved}>{label}</Link>
                                    )}
                                </DropdownMenuItem>
                            );
                        }

                        return (
                            <DropdownMenuItem
                                key={actionKey}
                                {...(cfg.action
                                    ? {
                                          'data-flatpack-action': cfg.action,
                                      }
                                    : {})}
                            >
                                {label}
                            </DropdownMenuItem>
                        );
                    })}
                    {primary.length > 0 && destructive.length > 0 ? (
                        <DropdownMenuSeparator />
                    ) : null}
                    {destructive.map(([actionKey, cfg]) => {
                        const template = cfg.href ?? cfg.url ?? '';
                        const resolved = template
                            ? interpolateRowPlaceholders(template, row)
                            : '';
                        const label = actionRowLabel(cfg, actionKey);

                        if (resolved) {
                            const external = /^https?:\/\//i.test(resolved);
                            return (
                                <DropdownMenuItem
                                    key={actionKey}
                                    variant="destructive"
                                    asChild
                                    {...(cfg.action
                                        ? {
                                              'data-flatpack-action':
                                                  cfg.action,
                                          }
                                        : {})}
                                >
                                    {external ? (
                                        <a
                                            href={resolved}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {label}
                                        </a>
                                    ) : (
                                        <Link href={resolved}>{label}</Link>
                                    )}
                                </DropdownMenuItem>
                            );
                        }

                        return (
                            <DropdownMenuItem
                                key={actionKey}
                                variant="destructive"
                                {...(cfg.action
                                    ? {
                                          'data-flatpack-action': cfg.action,
                                      }
                                    : {})}
                            >
                                {label}
                            </DropdownMenuItem>
                        );
                    })}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
