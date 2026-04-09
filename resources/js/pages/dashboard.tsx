import { Head, usePage } from '@inertiajs/react';
import { ChartAreaInteractive } from '@/components/chart-area-interactive';
import { DataTable } from '@/components/data-table';
import { SectionCards } from '@/components/section-cards';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import data from '@/data/data.json';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackPageProps } from '@/types/flatpack';

export default function FlatpackDashboard() {
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();
    const { user } = flatpack ?? {};

    return (
        <div className="flex flex-col gap-4 md:gap-6">
            <Head title="Flatpack" />
            <div className="flex flex-col gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground">
                        Welcome back, {user?.name}!
                    </p>
                </div>
                <div className="relative min-h-[40vh] overflow-hidden rounded-xl border border-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
            <SectionCards />
            <ChartAreaInteractive />
            <DataTable data={data} />
        </div>
    );
}

FlatpackDashboard.layout = (page: React.ReactNode) => (
    <FlatpackLayout title="Flatpack admin">{page}</FlatpackLayout>
);
