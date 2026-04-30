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
import { buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { isDestructiveActionButton } from '@/lib/data-table-action-semantics';
import { interpolateRowPlaceholders } from '@/lib/data-table-utils';
import { cn } from '@/lib/utils';
import type {
    DataTableRowActionPayload,
    FlatpackDataTableActionButton,
} from '@/types/data-table';

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

function stableRowActionKey(cfg: FlatpackDataTableActionButton): string {
    return [
        cfg.action ?? '',
        cfg.label,
        cfg.href ?? '',
        cfg.variant ?? '',
        cfg.icon ?? '',
    ].join('|');
}

function partitionRowActions(actions: FlatpackDataTableActionButton[]): {
    primary: FlatpackDataTableActionButton[];
    destructive: FlatpackDataTableActionButton[];
} {
    const primary: FlatpackDataTableActionButton[] = [];
    const destructive: FlatpackDataTableActionButton[] = [];
    for (const cfg of actions) {
        const slug = cfg.action ?? cfg.label;
        if (isDestructiveActionButton(slug, cfg)) {
            destructive.push(cfg);
        } else {
            primary.push(cfg);
        }
    }
    return { primary, destructive };
}

function ActionRowLabel({
    cfg,
    actionSlug,
}: {
    cfg: FlatpackDataTableActionButton;
    actionSlug: string;
}) {
    const Icon = iconForAction(cfg.icon) ?? iconForAction(actionSlug);
    const displayLabel = truncateActionMenuLabel(cfg.label);
    const charTruncated = displayLabel !== cfg.label;
    return (
        <span className="flex items-center gap-2">
            {Icon ? (
                <Icon className="size-3.5 shrink-0 opacity-70" aria-hidden />
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
}

function RowActionMenuItem({
    cfg,
    row,
    destructive,
    onAction,
}: {
    cfg: FlatpackDataTableActionButton;
    row: Record<string, unknown>;
    destructive: boolean;
    onAction?: (payload: DataTableRowActionPayload) => void | Promise<void>;
}) {
    const slug = cfg.action ?? cfg.label;
    const template = cfg.href ?? '';
    const resolvedHref = template
        ? interpolateRowPlaceholders(template, row)
        : '';
    const variantProps = destructive ? { variant: 'destructive' as const } : {};
    const dataAttrs = cfg.action
        ? ({ 'data-flatpack-action': cfg.action } as const)
        : {};

    const label = <ActionRowLabel cfg={cfg} actionSlug={slug} />;

    if (resolvedHref) {
        const external = /^https?:\/\//i.test(resolvedHref);
        return (
            <DropdownMenuItem asChild {...variantProps} {...dataAttrs}>
                {external ? (
                    <a
                        href={resolvedHref}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {label}
                    </a>
                ) : (
                    <Link href={resolvedHref}>{label}</Link>
                )}
            </DropdownMenuItem>
        );
    }

    return (
        <DropdownMenuItem
            {...variantProps}
            onSelect={() => {
                if (cfg.action) {
                    void onAction?.({
                        action: cfg.action,
                        row,
                        button: cfg,
                    });
                }
            }}
        >
            {label}
        </DropdownMenuItem>
    );
}

export function DataTableActionsCell({
    actions,
    row,
    onAction,
    requireRowId = false,
}: {
    actions: FlatpackDataTableActionButton[];
    row: Record<string, unknown>;
    onAction?: (payload: DataTableRowActionPayload) => void | Promise<void>;
    requireRowId?: boolean;
}) {
    const { primary, destructive } = partitionRowActions(actions);
    const rowIdRaw = row.id;
    const hasUsableRowId =
        rowIdRaw !== null &&
        rowIdRaw !== undefined &&
        String(rowIdRaw).trim() !== '';
    if (requireRowId && !hasUsableRowId) {
        return null;
    }

    return (
        <div className="flex justify-end" data-no-row-click>
            <DropdownMenu>
                <DropdownMenuTrigger
                    className={cn(
                        buttonVariants({ variant: 'ghost', size: 'icon' }),
                        'size-8 text-muted-foreground data-[state=open]:bg-muted',
                    )}
                >
                    <EllipsisVerticalIcon className="size-4" />
                    <span className="sr-only">Open row actions</span>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    className="min-w-44 max-w-xs"
                    onCloseAutoFocus={(event) => event.preventDefault()}
                >
                    {primary.map((cfg) => (
                        <RowActionMenuItem
                            key={`p-${stableRowActionKey(cfg)}`}
                            cfg={cfg}
                            row={row}
                            destructive={false}
                            onAction={onAction}
                        />
                    ))}
                    {primary.length > 0 && destructive.length > 0 ? (
                        <DropdownMenuSeparator />
                    ) : null}
                    {destructive.map((cfg) => (
                        <RowActionMenuItem
                            key={`d-${stableRowActionKey(cfg)}`}
                            cfg={cfg}
                            row={row}
                            destructive
                            onAction={onAction}
                        />
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
