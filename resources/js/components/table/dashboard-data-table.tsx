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
import type { DashboardSectionsTableCatalog } from '@/types/dashboard';

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
            className="flex w-full flex-col justify-start gap-6"
        >
            <div className="flex min-w-0 flex-col gap-2 @4xl/main:flex-row @4xl/main:items-center">
                <Label htmlFor="dashboard-view-selector" className="sr-only">
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
                            <SelectItem value="outline">Outline</SelectItem>
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
                        Past Performance <Badge variant="secondary">3</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="key-personnel">
                        Key Personnel <Badge variant="secondary">2</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="focus-documents">
                        Focus Documents
                    </TabsTrigger>
                </TabsList>
            </div>

            <TabsContent
                value="outline"
                className="relative flex flex-col gap-4 overflow-auto outline-none data-[state=inactive]:hidden"
            >
                <DataTable
                    checkboxes={catalog.props.checkboxes}
                    className="gap-4"
                    columns={columns}
                    data={rows}
                    id={catalog.id}
                    reorderable={catalog.props.reorderable}
                    toolbarAfterColumns={
                        <Button type="button" variant="outline" size="sm">
                            <PlusIcon
                                className="size-4 opacity-80"
                                aria-hidden
                            />
                            <span className="hidden lg:inline">
                                Add Section
                            </span>
                        </Button>
                    }
                />
            </TabsContent>

            <TabsContent
                value="past-performance"
                className="flex flex-col px-4 outline-none data-[state=inactive]:hidden lg:px-6"
            >
                <div className="aspect-video w-full flex-1 rounded-lg border border-dashed" />
            </TabsContent>
            <TabsContent
                value="key-personnel"
                className="flex flex-col px-4 outline-none data-[state=inactive]:hidden lg:px-6"
            >
                <div className="aspect-video w-full flex-1 rounded-lg border border-dashed" />
            </TabsContent>
            <TabsContent
                value="focus-documents"
                className="flex flex-col px-4 outline-none data-[state=inactive]:hidden lg:px-6"
            >
                <div className="aspect-video w-full flex-1 rounded-lg border border-dashed" />
            </TabsContent>
        </Tabs>
    );
}
