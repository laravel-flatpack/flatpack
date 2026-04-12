/**
 * Dashboard sections table built on {@link DataTable}.
 *
 * Parity vs the former `data-table-showcase` (intentional / not ported):
 * - **Reviewer** column omitted (per product decision).
 * - Row drawer has **no** Recharts block, trending blurb, or mobile-only layout split; it only edits schema fields (same as other `DataTable` drawers).
 * - **No `toast()`** on save; cells commit on blur / change like the shared table field.
 * - **Target / limit** headers are not right-aligned; inputs use the shared full-width dashboard input style (not `w-16` numeric chips).
 * - **Reorder**: `sort_order` is reindexed 1…n after each drag; row `id` stays stable (the old showcase only reordered the array).
 * - **Non-outline tabs** remain dashed placeholder panels only (no real content).
 */
import { PlusIcon } from 'lucide-react';
import * as React from 'react';
import { DataTable } from '@/components/table/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type {
    FlatpackDataTableColumn,
    FlatpackDataTableColumnOption,
} from '@/types/data-table';

/** Values that appear in `resources/js/data/data.json` plus common aliases for the drawer. */
const SECTION_TYPE_OPTIONS: FlatpackDataTableColumnOption[] = [
    { value: 'Cover page', label: 'Cover page' },
    { value: 'Table of contents', label: 'Table of contents' },
    { value: 'Narrative', label: 'Narrative' },
    { value: 'Technical content', label: 'Technical content' },
    { value: 'Plain language', label: 'Plain language' },
    { value: 'Legal', label: 'Legal' },
    { value: 'Visual', label: 'Visual' },
    { value: 'Financial', label: 'Financial' },
    { value: 'Research', label: 'Research' },
    { value: 'Planning', label: 'Planning' },
    { value: 'Table of Contents', label: 'Table of Contents' },
    { value: 'Executive Summary', label: 'Executive Summary' },
    { value: 'Technical Approach', label: 'Technical Approach' },
    { value: 'Design', label: 'Design' },
    { value: 'Capabilities', label: 'Capabilities' },
    { value: 'Focus Documents', label: 'Focus Documents' },
    { value: 'Cover Page', label: 'Cover Page' },
];

const DASHBOARD_COLUMNS: FlatpackDataTableColumn[] = [
    {
        id: 'header',
        label: 'Header',
        detailDrawer: true,
        editable: true,
        sortable: true,
    },
    {
        id: 'type',
        label: 'Section Type',
        type: 'badge',
        options: SECTION_TYPE_OPTIONS,
        sortable: true,
    },
    {
        id: 'status',
        label: 'Status',
        type: 'select',
        sortable: true,
        options: [
            { value: 'Done', label: 'Done', status: 'success' },
            { value: 'In Process', label: 'In Process', status: 'pending' },
            { value: 'Not Started', label: 'Not Started', status: 'pending' },
        ],
    },
    {
        id: 'target',
        label: 'Target',
        editable: true,
        sortable: true,
    },
    {
        id: 'limit',
        label: 'Limit',
        editable: true,
        sortable: true,
    },
    {
        id: 'actions',
        label: 'Actions',
        type: 'actions',
        buttons: {
            edit: { label: 'Edit', icon: 'edit', action: 'edit' },
            copy: { label: 'Make a copy', action: 'copy' },
            favorite: { label: 'Favorite', action: 'favorite' },
            delete: {
                label: 'Delete',
                icon: 'delete',
                action: 'delete',
            },
        },
    },
];

export type DashboardTableRow = Record<string, unknown> & {
    id: number;
    header: string;
    type: string;
    status: string;
    target: string;
    limit: string;
};

export function DashboardDataTable({
    data: rawRows,
}: {
    data: DashboardTableRow[];
}) {
    const [view, setView] = React.useState('outline');

    const rows = React.useMemo(
        () =>
            rawRows.map((row, i) => ({
                ...row,
                sort_order: i + 1,
            })),
        [rawRows],
    );

    return (
        <Tabs
            value={view}
            onValueChange={setView}
            className="w-full flex-col justify-start gap-6"
        >
            <DataTable
                checkboxes
                className="gap-4"
                columns={DASHBOARD_COLUMNS}
                data={rows}
                id="dashboard-sections-table"
                primaryTabPanelValue="outline"
                reorderable
                tabPanels={
                    <>
                        <TabsContent
                            value="past-performance"
                            className="flex flex-col px-4 outline-none lg:px-6"
                        >
                            <div className="aspect-video w-full flex-1 rounded-lg border border-dashed" />
                        </TabsContent>
                        <TabsContent
                            value="key-personnel"
                            className="flex flex-col px-4 outline-none lg:px-6"
                        >
                            <div className="aspect-video w-full flex-1 rounded-lg border border-dashed" />
                        </TabsContent>
                        <TabsContent
                            value="focus-documents"
                            className="flex flex-col px-4 outline-none lg:px-6"
                        >
                            <div className="aspect-video w-full flex-1 rounded-lg border border-dashed" />
                        </TabsContent>
                    </>
                }
                toolbarAfterColumns={
                    <Button type="button" variant="outline" size="sm">
                        <PlusIcon className="size-4 opacity-80" aria-hidden />
                        <span className="hidden lg:inline">Add Section</span>
                    </Button>
                }
                toolbarStart={
                    <>
                        <Label
                            htmlFor="dashboard-view-selector"
                            className="sr-only"
                        >
                            View
                        </Label>
                        <Select value={view} onValueChange={setView}>
                            <SelectTrigger
                                className="flex w-fit @4xl/main:hidden"
                                size="sm"
                                id="dashboard-view-selector"
                            >
                                <SelectValue placeholder="Select a view" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="outline">
                                        Outline
                                    </SelectItem>
                                    <SelectItem value="past-performance">
                                        Past Performance
                                    </SelectItem>
                                    <SelectItem value="key-personnel">
                                        Key Personnel
                                    </SelectItem>
                                    <SelectItem value="focus-documents">
                                        Focus Documents
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <TabsList className="hidden **:data-[slot=badge]:size-5 **:data-[slot=badge]:rounded-full **:data-[slot=badge]:bg-muted-foreground/30 **:data-[slot=badge]:px-1 @4xl/main:flex">
                            <TabsTrigger value="outline">Outline</TabsTrigger>
                            <TabsTrigger value="past-performance">
                                Past Performance{' '}
                                <Badge variant="secondary">3</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="key-personnel">
                                Key Personnel{' '}
                                <Badge variant="secondary">2</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="focus-documents">
                                Focus Documents
                            </TabsTrigger>
                        </TabsList>
                    </>
                }
            />
        </Tabs>
    );
}
