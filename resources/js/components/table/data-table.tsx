import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
import {
    arrayMove,
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Link } from '@inertiajs/react';
import {
    type Column,
    type ColumnDef,
    type ColumnFiltersState,
    flexRender,
    getCoreRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    type Row,
    type SortingState,
    useReactTable,
    type VisibilityState,
} from '@tanstack/react-table';
import {
    AlertTriangleIcon,
    ArrowDownIcon,
    ArrowUpDownIcon,
    ArrowUpIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronsLeftIcon,
    ChevronsRightIcon,
    CircleCheckIcon,
    CircleXIcon,
    Columns3Icon,
    EllipsisVerticalIcon,
    GripVerticalIcon,
    LoaderIcon,
    type LucideIcon,
    PencilIcon,
    Trash2Icon,
} from 'lucide-react';
import * as React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { TabsContent } from '@/components/ui/tabs';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import type {
    FlatpackDataTableActionButton,
    FlatpackDataTableColumn,
    FlatpackDataTableColumnOption,
    FlatpackDataTableSelectOptionStatus,
} from '@/types/data-table';

function stableRowId(row: Record<string, unknown>, index: number): string {
    const idVal = row.id;
    if (idVal !== undefined && idVal !== null) {
        return String(idVal);
    }
    return `row-${index}`;
}

/** Same look as dashboard `DashboardDataTable` target/limit inputs. */
const DASHBOARD_TABLE_INPUT_CLASS =
    'h-8 min-w-0 w-full max-w-full border-transparent bg-transparent text-sm shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30';

const DASHBOARD_TABLE_SELECT_TRIGGER_CLASS =
    'h-8 w-full min-w-0 max-w-full border-transparent bg-transparent shadow-none hover:bg-input/30 focus-visible:border focus-visible:bg-background dark:bg-transparent dark:hover:bg-input/30 dark:focus-visible:bg-input/30 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate';

function cellControlDomId(rowId: string, columnId: string): string {
    return `dt-${String(rowId).replace(/[^a-zA-Z0-9_-]/g, '-')}-${columnId}`;
}

function reindexReorderColumn(
    rows: Record<string, unknown>[],
    reorderKey: string,
): Record<string, unknown>[] {
    return rows.map((row, i) => ({
        ...row,
        [reorderKey]: i + 1,
    }));
}

function interpolateRowPlaceholders(
    template: string,
    row: Record<string, unknown>,
): string {
    return template.replace(/\{([^}]+)\}/g, (_, rawKey: string) => {
        const key = rawKey.trim();
        const v = row[key];
        if (v == null) {
            return '';
        }
        return String(v);
    });
}

function iconForAction(iconOrKey?: string): LucideIcon | null {
    const k = iconOrKey?.toLowerCase() ?? '';
    if (k === 'edit' || k === 'pencil') {
        return PencilIcon;
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

function ActionsCell({
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
                <DropdownMenuContent align="end" className="min-w-32">
                    {primary.map(([actionKey, cfg]) => {
                        const template = cfg.href ?? cfg.url ?? '';
                        const resolved = template
                            ? interpolateRowPlaceholders(template, row)
                            : '';
                        const Icon =
                            iconForAction(cfg.icon) ?? iconForAction(actionKey);
                        const label = (
                            <span className="flex items-center gap-2">
                                {Icon ? (
                                    <Icon
                                        className="size-3.5 shrink-0 opacity-70"
                                        aria-hidden
                                    />
                                ) : null}
                                {cfg.label}
                            </span>
                        );

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
                        const Icon =
                            iconForAction(cfg.icon) ?? iconForAction(actionKey);
                        const label = (
                            <span className="flex items-center gap-2">
                                {Icon ? (
                                    <Icon
                                        className="size-3.5 shrink-0 opacity-70"
                                        aria-hidden
                                    />
                                ) : null}
                                {cfg.label}
                            </span>
                        );

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

function formatCellValue(raw: unknown): string {
    if (raw == null) {
        return '';
    }
    if (typeof raw === 'object') {
        return JSON.stringify(raw);
    }
    return String(raw);
}

function dateInputSegment(raw: unknown): string {
    const s = formatCellValue(raw);
    const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
    return m ? m[1] : '';
}

function mergeCommittedDate(isoDay: string, previous: unknown): string {
    const prev = formatCellValue(previous);
    if (isoDay === '') {
        return '';
    }
    if (prev.length > 10 && (prev[10] === ' ' || prev[10] === 'T')) {
        return isoDay + prev.slice(10);
    }
    return isoDay;
}

function EditableTextCell({
    value,
    commit,
    ariaLabel,
    controlId,
}: {
    value: unknown;
    commit: (next: unknown) => void;
    ariaLabel: string;
    controlId: string;
}) {
    const [draft, setDraft] = React.useState(() => formatCellValue(value));
    React.useLayoutEffect(() => {
        setDraft(formatCellValue(value));
    }, [value]);

    return (
        <>
            <Label htmlFor={controlId} className="sr-only">
                {ariaLabel}
            </Label>
            <Input
                id={controlId}
                className={DASHBOARD_TABLE_INPUT_CLASS}
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                onBlur={() => {
                    if (draft !== formatCellValue(value)) {
                        commit(draft);
                    }
                }}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        (e.target as HTMLInputElement).blur();
                    }
                    if (e.key === 'Escape') {
                        setDraft(formatCellValue(value));
                        (e.target as HTMLInputElement).blur();
                    }
                }}
            />
        </>
    );
}

function columnEditableInDrawer(col: FlatpackDataTableColumn): boolean {
    if (col.type === 'actions') {
        return false;
    }
    if (col.editable === true) {
        return true;
    }
    if (col.type === 'select' && col.options?.length) {
        return true;
    }
    if (col.type === 'badge' && col.options?.length) {
        return true;
    }
    if (col.type === 'date') {
        return true;
    }
    return false;
}

function DrawerRowField({
    col,
    value,
    onChange,
}: {
    col: FlatpackDataTableColumn;
    value: unknown;
    onChange: (next: unknown) => void;
}) {
    const fieldId = `drawer-field-${col.id}`;

    if (
        (col.type === 'select' ||
            (col.type === 'badge' && col.options?.length)) &&
        col.options?.length
    ) {
        const str = value == null ? '' : String(value);
        const validOption = col.options.some((o) => o.value === str);
        return (
            <div className="flex flex-col gap-2">
                <Label htmlFor={fieldId}>{col.label}</Label>
                <Select
                    value={str !== '' && validOption ? str : undefined}
                    onValueChange={(v) => onChange(v)}
                >
                    <SelectTrigger id={fieldId} className="w-full">
                        <SelectValue placeholder="Choose…" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            {col.options.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
        );
    }

    if (col.type === 'date') {
        const day = dateInputSegment(value);
        return (
            <div className="flex flex-col gap-2">
                <Label htmlFor={fieldId}>{col.label}</Label>
                <Input
                    id={fieldId}
                    type="date"
                    className="w-full tabular-nums"
                    value={day}
                    onChange={(e) =>
                        onChange(mergeCommittedDate(e.target.value, value))
                    }
                />
            </div>
        );
    }

    if (columnEditableInDrawer(col)) {
        const text = formatCellValue(value);
        return (
            <div className="flex flex-col gap-2">
                <Label htmlFor={fieldId}>{col.label}</Label>
                <Input
                    id={fieldId}
                    className="w-full"
                    value={text}
                    onChange={(e) => onChange(e.target.value)}
                />
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-1">
            <span className="text-xs font-medium text-muted-foreground">
                {col.label}
            </span>
            <span className="text-foreground">
                {formatCellValue(value) || '—'}
            </span>
        </div>
    );
}

function RowDetailDrawer({
    triggerColumn,
    row,
    rowId,
    schemaColumns,
    onRowReplace,
}: {
    triggerColumn: FlatpackDataTableColumn;
    row: Record<string, unknown>;
    rowId: string;
    schemaColumns: FlatpackDataTableColumn[];
    onRowReplace: (rowId: string, nextRow: Record<string, unknown>) => void;
}) {
    const isMobile = useIsMobile();
    const [open, setOpen] = React.useState(false);
    const [draft, setDraft] = React.useState<Record<string, unknown>>(row);
    const drawerTriggerRef = React.useRef<HTMLButtonElement>(null);

    React.useLayoutEffect(() => {
        if (open) {
            setDraft({ ...row });
        }
    }, [open, row]);

    const setField = React.useCallback((columnId: string, next: unknown) => {
        setDraft((d) => ({ ...d, [columnId]: next }));
    }, []);

    const openDrawer = React.useCallback(() => {
        // Blur before open so Vaul’s aria-hidden on page content does not hide a focused control (browser a11y warning).
        drawerTriggerRef.current?.blur();
        setOpen(true);
    }, []);

    const formColumns = schemaColumns.filter((c) => c.type !== 'actions');

    return (
        <Drawer
            direction={isMobile ? 'bottom' : 'right'}
            onOpenChange={setOpen}
            open={open}
        >
            <Button
                ref={drawerTriggerRef}
                type="button"
                variant="link"
                aria-expanded={open}
                aria-haspopup="dialog"
                className="h-auto min-h-0 w-fit max-w-full justify-start px-0 py-0 text-left font-normal text-foreground"
                onClick={openDrawer}
            >
                <span className="truncate">
                    {formatCellValue(row[triggerColumn.id]) || '—'}
                </span>
            </Button>
            <DrawerContent>
                <DrawerHeader className="gap-1">
                    <DrawerTitle>
                        {formatCellValue(draft[triggerColumn.id]) ||
                            triggerColumn.label}
                    </DrawerTitle>
                    <DrawerDescription>
                        Edit row fields and save your changes.
                    </DrawerDescription>
                </DrawerHeader>
                <div className="flex flex-col gap-4 overflow-y-auto px-4 pb-2 text-sm">
                    <div className="flex flex-col gap-4">
                        {formColumns.map((c) => (
                            <DrawerRowField
                                key={c.id}
                                col={c}
                                value={draft[c.id]}
                                onChange={(v) => setField(c.id, v)}
                            />
                        ))}
                    </div>
                </div>
                <DrawerFooter>
                    <Button
                        type="button"
                        onClick={() => {
                            onRowReplace(rowId, draft);
                            setOpen(false);
                        }}
                    >
                        Save changes
                    </Button>
                    <DrawerClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </DrawerClose>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}

const SELECT_OPTION_STATUS_ICONS: Record<
    FlatpackDataTableSelectOptionStatus,
    React.ReactElement
> = {
    success: (
        <CircleCheckIcon
            className="size-3.5 shrink-0 fill-green-500 dark:fill-green-400"
            aria-hidden
        />
    ),
    pending: (
        <LoaderIcon className="size-3.5 shrink-0 opacity-80" aria-hidden />
    ),
    warning: (
        <AlertTriangleIcon
            className="size-3.5 shrink-0 text-amber-600 dark:text-amber-400"
            aria-hidden
        />
    ),
    error: (
        <CircleXIcon
            className="size-3.5 shrink-0 text-red-600 dark:text-red-400"
            aria-hidden
        />
    ),
};

/** Leading icon from option `status` (set in column schema, e.g. PHP). */
function selectOptionLeadingIcon(
    option: FlatpackDataTableColumnOption | undefined,
): React.ReactNode {
    const s = option?.status;
    if (s === undefined) {
        return null;
    }
    return SELECT_OPTION_STATUS_ICONS[s] ?? null;
}

function SchemaTableCell({
    column: col,
    value,
    rowId,
    row,
    schemaColumns,
    onCellChange,
    onRowReplace,
}: {
    column: FlatpackDataTableColumn;
    value: unknown;
    rowId: string;
    row: Record<string, unknown>;
    schemaColumns: FlatpackDataTableColumn[];
    onCellChange?: (rowId: string, columnId: string, next: unknown) => void;
    onRowReplace?: (rowId: string, nextRow: Record<string, unknown>) => void;
}) {
    if (col.detailDrawer === true && onRowReplace) {
        return (
            <RowDetailDrawer
                onRowReplace={onRowReplace}
                row={row}
                rowId={rowId}
                schemaColumns={schemaColumns}
                triggerColumn={col}
            />
        );
    }

    const editable =
        col.editable === true &&
        col.type !== 'actions' &&
        onCellChange != null &&
        col.detailDrawer !== true;
    const commit = (next: unknown) => onCellChange?.(rowId, col.id, next);
    const controlId = cellControlDomId(rowId, col.id);

    if (col.type === 'badge') {
        const text = formatCellValue(value);
        return (
            <div className="w-32 min-w-0 shrink-0">
                <Badge
                    variant="outline"
                    className="max-w-full px-1.5 font-normal text-muted-foreground"
                >
                    <span className="truncate">{text || '—'}</span>
                </Badge>
            </div>
        );
    }

    if (col.type === 'select' && col.options?.length) {
        const str = value == null ? '' : String(value);
        if (editable) {
            const validOption = col.options.some((o) => o.value === str);
            return (
                <>
                    <Label htmlFor={controlId} className="sr-only">
                        {col.label}
                    </Label>
                    <Select
                        value={str !== '' && validOption ? str : undefined}
                        onValueChange={(v) => commit(v)}
                    >
                        <SelectTrigger
                            id={controlId}
                            size="sm"
                            className={DASHBOARD_TABLE_SELECT_TRIGGER_CLASS}
                        >
                            <SelectValue placeholder="Choose…" />
                        </SelectTrigger>
                        <SelectContent align="end">
                            <SelectGroup>
                                {col.options.map((o) => (
                                    <SelectItem key={o.value} value={o.value}>
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </>
            );
        }
        const opt = col.options.find((o) => o.value === str);
        const display = opt?.label ?? str;
        return (
            <Badge
                variant="outline"
                className="inline-flex max-w-full items-center gap-1.5 px-1.5 font-normal text-muted-foreground"
            >
                {selectOptionLeadingIcon(opt)}
                <span className="truncate">{display}</span>
            </Badge>
        );
    }

    if (col.type === 'date') {
        if (editable) {
            const day = dateInputSegment(value);
            return (
                <>
                    <Label htmlFor={controlId} className="sr-only">
                        {col.label}
                    </Label>
                    <Input
                        id={controlId}
                        type="date"
                        className={cn(
                            DASHBOARD_TABLE_INPUT_CLASS,
                            'tabular-nums',
                        )}
                        value={day}
                        onChange={(e) =>
                            commit(mergeCommittedDate(e.target.value, value))
                        }
                    />
                </>
            );
        }
        const s = formatCellValue(value);
        if (!s) {
            return <span className="text-muted-foreground">—</span>;
        }
        const datePart = s.slice(0, 10);
        return (
            <span className="tabular-nums text-muted-foreground">
                {datePart}
            </span>
        );
    }

    if (editable) {
        return (
            <EditableTextCell
                value={value}
                commit={commit}
                ariaLabel={col.label}
                controlId={controlId}
            />
        );
    }

    return (
        <span className="block min-w-0 truncate" title={formatCellValue(value)}>
            {formatCellValue(value)}
        </span>
    );
}

function DataTableColumnHeader({
    column,
    label,
}: {
    column: Column<Record<string, unknown>, unknown>;
    label: string;
}) {
    const sorted = column.getIsSorted();
    return (
        <Button
            type="button"
            variant="ghost"
            className={cn('-ml-2 h-8 gap-1 px-2 font-medium has-[>svg]:px-2')}
            aria-sort={
                sorted === 'asc'
                    ? 'ascending'
                    : sorted === 'desc'
                      ? 'descending'
                      : 'none'
            }
            onClick={column.getToggleSortingHandler()}
        >
            {label}
            {sorted === 'desc' ? (
                <ArrowDownIcon
                    className="size-3.5 shrink-0 opacity-80"
                    aria-hidden
                />
            ) : sorted === 'asc' ? (
                <ArrowUpIcon
                    className="size-3.5 shrink-0 opacity-80"
                    aria-hidden
                />
            ) : (
                <ArrowUpDownIcon
                    className="size-3.5 shrink-0 opacity-45"
                    aria-hidden
                />
            )}
        </Button>
    );
}

export function buildDataTableColumnDefs(
    schemaColumns: FlatpackDataTableColumn[],
    options: {
        checkboxes?: boolean;
        reorderable?: boolean;
        onCellChange?: (
            rowId: string,
            columnId: string,
            value: unknown,
        ) => void;
        onRowReplace?: (
            rowId: string,
            nextRow: Record<string, unknown>,
        ) => void;
    },
): ColumnDef<Record<string, unknown>>[] {
    const defs: ColumnDef<Record<string, unknown>>[] = [];

    if (options.reorderable) {
        defs.push({
            id: 'drag',
            header: () => <span className="sr-only">Reorder</span>,
            cell: () => null,
            enableSorting: false,
            enableHiding: false,
        });
    }

    if (options.checkboxes) {
        defs.push({
            id: 'select',
            header: ({ table }) => (
                <div className="flex items-center justify-start">
                    <Checkbox
                        checked={
                            table.getIsAllPageRowsSelected() ||
                            (table.getIsSomePageRowsSelected() &&
                                'indeterminate')
                        }
                        onCheckedChange={(value) =>
                            table.toggleAllPageRowsSelected(!!value)
                        }
                        aria-label="Select all"
                    />
                </div>
            ),
            cell: ({ row }) => (
                <div className="flex items-center justify-center">
                    <Checkbox
                        checked={row.getIsSelected()}
                        onCheckedChange={(value) => row.toggleSelected(!!value)}
                        aria-label="Select row"
                    />
                </div>
            ),
            enableSorting: false,
            enableHiding: false,
        });
    }

    for (const col of schemaColumns) {
        const columnSortable = col.type !== 'actions' && col.sortable === true;
        defs.push({
            id: col.id,
            accessorKey: col.id,
            header: columnSortable
                ? ({ column }) => (
                      <DataTableColumnHeader
                          column={column}
                          label={col.label}
                      />
                  )
                : col.label,
            enableSorting: columnSortable,
            enableHiding: true,
            cell: ({ row }) => {
                if (col.type === 'actions') {
                    if (!col.buttons || Object.keys(col.buttons).length === 0) {
                        return <span className="text-muted-foreground">—</span>;
                    }
                    return (
                        <ActionsCell buttons={col.buttons} row={row.original} />
                    );
                }
                return (
                    <SchemaTableCell
                        column={col}
                        row={row.original}
                        rowId={row.id}
                        schemaColumns={schemaColumns}
                        value={row.getValue(col.id)}
                        onCellChange={options.onCellChange}
                        onRowReplace={options.onRowReplace}
                    />
                );
            },
        });
    }

    return defs;
}

function visibilityFromSchema(
    columns: FlatpackDataTableColumn[],
): VisibilityState {
    const state: VisibilityState = {};
    for (const col of columns) {
        if (col.invisible) {
            state[col.id] = false;
        }
    }
    return state;
}

/** Full leaf column id list in schema order (incl. hidden). Keeps TanStack from appending shown columns at the end. */
function leafColumnIdsInSchemaOrder(
    schemaColumns: FlatpackDataTableColumn[],
    checkboxes: boolean,
    reorderable: boolean,
): string[] {
    return [
        ...(reorderable ? ['drag'] : []),
        ...(checkboxes ? ['select'] : []),
        ...schemaColumns.map((c) => c.id),
    ];
}

function SchemaDraggableRow({ row }: { row: Row<Record<string, unknown>> }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: row.id });

    return (
        <TableRow
            ref={setNodeRef}
            data-state={row.getIsSelected() && 'selected'}
            data-dragging={isDragging}
            className="relative z-0 data-[dragging=true]:z-10 data-[dragging=true]:opacity-80"
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
        >
            {row.getVisibleCells().map((cell) => (
                <TableCell key={cell.id}>
                    {cell.column.id === 'drag' ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-7 cursor-grab text-muted-foreground hover:bg-transparent active:cursor-grabbing"
                            {...attributes}
                            {...listeners}
                        >
                            <GripVerticalIcon
                                className="size-3.5 shrink-0"
                                aria-hidden
                            />
                            <span className="sr-only">Drag to reorder row</span>
                        </Button>
                    ) : (
                        flexRender(
                            cell.column.columnDef.cell,
                            cell.getContext(),
                        )
                    )}
                </TableCell>
            ))}
        </TableRow>
    );
}

export type DataTableProps = {
    /** Field id for accessibility (from form binding context). */
    id: string;
    columns: FlatpackDataTableColumn[];
    data: Record<string, unknown>[];
    checkboxes?: boolean;
    /**
     * Enables a drag handle column and row reordering. `true` reindexes `sort_order`; a string reindexes that column id.
     */
    reorderable?: boolean | string;
    onValueChange?: (value: unknown) => void;
    className?: string;
    /** Leading toolbar slot (e.g. view tabs). Renders left of the Columns control. */
    toolbarStart?: React.ReactNode;
    /** Slot after the Columns menu (e.g. primary action such as “Add section”). */
    toolbarAfterColumns?: React.ReactNode;
    /**
     * When this table is wrapped in a parent `Tabs` root, set to the tab value that should
     * show the grid + pagination (e.g. `"outline"`). Wraps those sections in `TabsContent`.
     */
    primaryTabPanelValue?: string;
    /**
     * Extra `TabsContent` nodes for other tab values, rendered as siblings after the primary panel.
     */
    tabPanels?: React.ReactNode;
};

/**
 * TanStack table driven by a Flatpack column schema and row `data` (plain objects).
 */
export function DataTable({
    id,
    columns: schemaColumns,
    data: initialData,
    checkboxes = false,
    reorderable: reorderableProp,
    onValueChange,
    className,
    toolbarStart,
    toolbarAfterColumns,
    primaryTabPanelValue,
    tabPanels,
}: DataTableProps) {
    const reorderKey =
        reorderableProp === true
            ? 'sort_order'
            : typeof reorderableProp === 'string'
              ? reorderableProp
              : null;
    const isReorderable = reorderKey !== null;

    const [data, setData] = React.useState<Record<string, unknown>[]>(
        () => initialData,
    );
    React.useLayoutEffect(() => {
        setData(initialData);
    }, [initialData]);

    const [rowSelection, setRowSelection] = React.useState({});
    const [columnVisibility, setColumnVisibility] =
        React.useState<VisibilityState>(() =>
            visibilityFromSchema(schemaColumns),
        );
    const schemaLeafOrder = React.useMemo(
        () =>
            leafColumnIdsInSchemaOrder(
                schemaColumns,
                checkboxes,
                isReorderable,
            ),
        [schemaColumns, checkboxes, isReorderable],
    );
    const [columnOrder, setColumnOrder] = React.useState<string[]>(() =>
        leafColumnIdsInSchemaOrder(schemaColumns, checkboxes, isReorderable),
    );
    React.useLayoutEffect(() => {
        setColumnOrder(schemaLeafOrder);
    }, [schemaLeafOrder]);
    const [columnFilters, setColumnFilters] =
        React.useState<ColumnFiltersState>([]);
    const [sorting, setSorting] = React.useState<SortingState>([]);
    const [pagination, setPagination] = React.useState({
        pageIndex: 0,
        pageSize: 10,
    });

    const handleCellChange = React.useCallback(
        (rowId: string, columnId: string, next: unknown) => {
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => stableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                const cur = prev[idx][columnId];
                if (Object.is(cur, next)) {
                    return prev;
                }
                const nextRows = prev.map((r, i) =>
                    i === idx ? { ...r, [columnId]: next } : r,
                );
                onValueChange?.(nextRows);
                return nextRows;
            });
        },
        [onValueChange],
    );

    const handleRowReplace = React.useCallback(
        (rowId: string, nextRow: Record<string, unknown>) => {
            setData((prev) => {
                const idx = prev.findIndex(
                    (row, index) => stableRowId(row, index) === rowId,
                );
                if (idx === -1) {
                    return prev;
                }
                const nextRows = prev.map((r, i) => (i === idx ? nextRow : r));
                onValueChange?.(nextRows);
                return nextRows;
            });
        },
        [onValueChange],
    );

    const columnDefs = React.useMemo(
        () =>
            buildDataTableColumnDefs(schemaColumns, {
                checkboxes,
                reorderable: isReorderable,
                onCellChange: handleCellChange,
                onRowReplace: handleRowReplace,
            }),
        [
            schemaColumns,
            checkboxes,
            isReorderable,
            handleCellChange,
            handleRowReplace,
        ],
    );

    const dndSensors = useSensors(
        useSensor(MouseSensor, {}),
        useSensor(TouchSensor, {}),
        useSensor(KeyboardSensor, {}),
    );
    const dndId = React.useId();

    const table = useReactTable({
        data,
        columns: columnDefs,
        state: {
            sorting,
            columnVisibility,
            columnOrder,
            rowSelection,
            columnFilters,
            pagination,
        },
        getRowId: (row, index) => stableRowId(row, index),
        enableRowSelection: checkboxes,
        onRowSelectionChange: setRowSelection,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onColumnOrderChange: setColumnOrder,
        onPaginationChange: setPagination,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
    });

    const handleDragEnd = React.useCallback(
        (event: DragEndEvent) => {
            if (!reorderKey) {
                return;
            }
            const { active, over } = event;
            if (!over || active.id === over.id) {
                return;
            }
            const pageRows = table.getRowModel().rows;
            const pageRowIds = pageRows.map((r) => r.id);
            const oldPageIdx = pageRowIds.indexOf(String(active.id));
            const newPageIdx = pageRowIds.indexOf(String(over.id));
            if (oldPageIdx === -1 || newPageIdx === -1) {
                return;
            }
            const fullIndices = pageRowIds.map((rowId) =>
                data.findIndex((row, idx) => stableRowId(row, idx) === rowId),
            );
            if (fullIndices.some((i) => i < 0)) {
                return;
            }
            const pageSlice: Record<string, unknown>[] = [];
            for (const i of fullIndices) {
                const row = data[i];
                if (row === undefined) {
                    return;
                }
                pageSlice.push(row);
            }
            const reorderedPage = arrayMove(pageSlice, oldPageIdx, newPageIdx);
            const next = [...data];
            for (let p = 0; p < fullIndices.length; p++) {
                const moved = reorderedPage[p];
                if (moved === undefined) {
                    return;
                }
                next[fullIndices[p]] = moved;
            }
            const withOrder = reindexReorderColumn(next, reorderKey);
            setData(withOrder);
            setSorting([]);
            onValueChange?.(withOrder);
        },
        [data, onValueChange, reorderKey, table],
    );

    const tableLabelId = `${id}-table-label`;

    const tableAndFooter = (
        <>
            <div className="overflow-hidden rounded-lg border">
                {isReorderable ? (
                    <DndContext
                        id={dndId}
                        collisionDetection={closestCenter}
                        modifiers={[restrictToVerticalAxis]}
                        onDragEnd={handleDragEnd}
                        sensors={dndSensors}
                    >
                        <Table>
                            <TableHeader className="sticky top-0 z-10 bg-muted">
                                {table.getHeaderGroups().map((headerGroup) => (
                                    <TableRow key={headerGroup.id}>
                                        {headerGroup.headers.map((header) => (
                                            <TableHead
                                                key={header.id}
                                                colSpan={header.colSpan}
                                            >
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(
                                                          header.column
                                                              .columnDef.header,
                                                          header.getContext(),
                                                      )}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                ))}
                            </TableHeader>
                            <TableBody>
                                {table.getRowModel().rows?.length ? (
                                    <SortableContext
                                        items={table
                                            .getRowModel()
                                            .rows.map((r) => r.id)}
                                        strategy={verticalListSortingStrategy}
                                    >
                                        {table.getRowModel().rows.map((row) => (
                                            <SchemaDraggableRow
                                                key={row.id}
                                                row={row}
                                            />
                                        ))}
                                    </SortableContext>
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={columnDefs.length}
                                            className="h-24 text-center"
                                        >
                                            No results.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </DndContext>
                ) : (
                    <Table>
                        <TableHeader className="sticky top-0 z-10 bg-muted">
                            {table.getHeaderGroups().map((headerGroup) => (
                                <TableRow key={headerGroup.id}>
                                    {headerGroup.headers.map((header) => (
                                        <TableHead
                                            key={header.id}
                                            colSpan={header.colSpan}
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            ))}
                        </TableHeader>
                        <TableBody>
                            {table.getRowModel().rows?.length ? (
                                table.getRowModel().rows.map((row) => (
                                    <TableRow
                                        key={row.id}
                                        data-state={
                                            row.getIsSelected() && 'selected'
                                        }
                                    >
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell key={cell.id}>
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={columnDefs.length}
                                        className="h-24 text-center"
                                    >
                                        No results.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                )}
            </div>

            <div className="flex flex-col gap-4 px-1 sm:flex-row sm:items-center sm:justify-between">
                {checkboxes ? (
                    <div className="text-sm text-muted-foreground">
                        {table.getFilteredSelectedRowModel().rows.length} of{' '}
                        {table.getFilteredRowModel().rows.length} row(s)
                        selected.
                    </div>
                ) : (
                    <div className="text-sm text-muted-foreground">
                        {table.getFilteredRowModel().rows.length} row(s).
                    </div>
                )}
                <div className="flex w-full flex-col gap-4 sm:w-auto sm:flex-row sm:items-center sm:gap-6">
                    <div className="flex items-center gap-2">
                        <Label
                            htmlFor={`${id}-rows-per-page`}
                            className="text-sm font-medium whitespace-nowrap"
                        >
                            Rows per page
                        </Label>
                        <Select
                            value={`${table.getState().pagination.pageSize}`}
                            onValueChange={(value) => {
                                table.setPageSize(Number(value));
                            }}
                        >
                            <SelectTrigger
                                size="sm"
                                className="w-20"
                                id={`${id}-rows-per-page`}
                            >
                                <SelectValue
                                    placeholder={
                                        table.getState().pagination.pageSize
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent side="top">
                                <SelectGroup>
                                    {[10, 20, 30, 40, 50].map((pageSize) => (
                                        <SelectItem
                                            key={pageSize}
                                            value={`${pageSize}`}
                                        >
                                            {pageSize}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-center justify-center gap-2 text-sm font-medium">
                        <span className="whitespace-nowrap">
                            Page {table.getState().pagination.pageIndex + 1} of{' '}
                            {table.getPageCount() || 1}
                        </span>
                        <div className="flex items-center gap-1">
                            <Button
                                variant="outline"
                                className="hidden size-8 p-0 sm:flex"
                                onClick={() => table.setPageIndex(0)}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <span className="sr-only">First page</span>
                                <ChevronsLeftIcon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                className="size-8"
                                size="icon"
                                onClick={() => table.previousPage()}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <span className="sr-only">Previous page</span>
                                <ChevronLeftIcon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                className="size-8"
                                size="icon"
                                onClick={() => table.nextPage()}
                                disabled={!table.getCanNextPage()}
                            >
                                <span className="sr-only">Next page</span>
                                <ChevronRightIcon className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                className="hidden size-8 sm:flex"
                                size="icon"
                                onClick={() =>
                                    table.setPageIndex(table.getPageCount() - 1)
                                }
                                disabled={!table.getCanNextPage()}
                            >
                                <span className="sr-only">Last page</span>
                                <ChevronsRightIcon className="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );

    const primaryPanel =
        primaryTabPanelValue != null ? (
            <TabsContent
                value={primaryTabPanelValue}
                className="relative flex flex-col gap-4 overflow-auto outline-none"
            >
                {tableAndFooter}
            </TabsContent>
        ) : (
            tableAndFooter
        );

    return (
        <div
            className={cn('flex w-full flex-col gap-4', className)}
            role="region"
            aria-labelledby={tableLabelId}
        >
            <span id={tableLabelId} className="sr-only">
                Data table
            </span>
            <div
                className={cn(
                    'flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center',
                    toolbarStart != null
                        ? 'sm:justify-between'
                        : 'sm:justify-end',
                )}
            >
                {toolbarStart != null ? (
                    <div className="flex min-w-0 flex-col gap-2 @4xl/main:flex-row @4xl/main:items-center">
                        {toolbarStart}
                    </div>
                ) : null}
                <div className="flex items-center justify-end gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm">
                                <Columns3Icon data-icon="inline-start" />
                                Columns
                                <ChevronDownIcon data-icon="inline-end" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            {table
                                .getAllColumns()
                                .filter(
                                    (column) =>
                                        typeof column.accessorFn !==
                                            'undefined' && column.getCanHide(),
                                )
                                .map((column) => (
                                    <DropdownMenuCheckboxItem
                                        key={column.id}
                                        className="capitalize"
                                        checked={column.getIsVisible()}
                                        onCheckedChange={(value) =>
                                            column.toggleVisibility(!!value)
                                        }
                                    >
                                        {typeof column.columnDef.header ===
                                        'string'
                                            ? column.columnDef.header
                                            : column.id}
                                    </DropdownMenuCheckboxItem>
                                ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                    {toolbarAfterColumns}
                </div>
            </div>

            {primaryPanel}
            {tabPanels}
        </div>
    );
}
