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
import type { FlatpackDataTableColumn } from '@/types/data-table';

/** Catalog entry shape aligned with DemoController's `data-table` demo item (`props` + `value`). */
export type DashboardSectionsTableCatalog = {
    id: string;
    title: string;
    description: string;
    props: {
        type: string;
        label: string;
        helperText: string;
        checkboxes: boolean;
        reorderable: boolean;
        actions: unknown[];
        columns: FlatpackDataTableColumn[];
    };
    showValue: boolean;
    value: DashboardTableRow[];
};

export type DashboardTableRow = Record<string, unknown> & {
    id: number;
    header: string;
    type: string;
    status: string;
    target: string;
    limit: string;
};

export function DashboardDataTable({
    catalog,
}: {
    catalog: DashboardSectionsTableCatalog;
}) {
    const [view, setView] = React.useState('outline');

    const columns = React.useMemo(
        () => catalog.props.columns,
        [catalog.props.columns],
    );

    const rows = React.useMemo(
        () =>
            catalog.value.map((row, i) => ({
                ...row,
                sort_order: i + 1,
            })),
        [catalog.value],
    );

    return (
        <Tabs
            value={view}
            onValueChange={setView}
            className="w-full flex-col justify-start gap-6"
        >
            <DataTable
                checkboxes={catalog.props.checkboxes}
                className="gap-4"
                columns={columns}
                data={rows}
                id={catalog.id}
                primaryTabPanelValue="outline"
                reorderable={catalog.props.reorderable}
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
