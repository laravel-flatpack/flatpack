import { Head } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { FlatpackDashboardWidgets } from '@/components/widgets/flatpack-dashboard-widgets';
import data from '@/data/data.json';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { DashboardSectionsTableCatalog } from '@/types/dashboard';
import type { FlatpackDashboardPageProps } from '@/types/pages/flatpack';

const ChartAreaInteractive = lazy(() =>
    import('@/components/chart-area-interactive').then((module) => ({
        default: module.ChartAreaInteractive,
    })),
);

const DashboardDataTable = lazy(() =>
    import('@/components/table/dashboard-data-table').then((module) => ({
        default: module.DashboardDataTable,
    })),
);

export default function FlatpackDashboard(props: FlatpackDashboardPageProps) {
    useCompositionDebugLog(props.composition_debug);

    return (
        <div className="flex flex-col gap-4 md:gap-6">
            <Head title="Dashboard" />
            <div className="flex flex-col gap-4">
                <div className="relative min-h-[40vh] overflow-hidden rounded-xl border border-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="col-span-1 md:col-span-2 xl:col-span-4">
                    <FlatpackDashboardWidgets
                        widgets={props.widgets}
                        tabPanels={props.widgets_schema?.tab_panels}
                    />
                </div>
            </div>
            <Suspense
                fallback={
                    <div className="h-96 animate-pulse rounded-xl border border-border bg-muted/40" />
                }
            >
                <ChartAreaInteractive />
            </Suspense>
            <Suspense
                fallback={
                    <div className="h-[32rem] animate-pulse rounded-xl border border-border bg-muted/40" />
                }
            >
                <DashboardDataTable
                    catalog={data as DashboardSectionsTableCatalog}
                />
            </Suspense>
        </div>
    );
}

FlatpackDashboard.layout = (page: React.ReactNode) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
