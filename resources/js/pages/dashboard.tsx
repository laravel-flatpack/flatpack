import { Head } from '@inertiajs/react';
import { DashboardWidgets } from '@/components/widgets/dashboard-widgets';
import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackDashboardPageProps } from '@/types/pages/flatpack';

export default function FlatpackDashboard(props: FlatpackDashboardPageProps) {
    useCompositionDebugLog(props.composition_debug);

    return (
        <div className="flex flex-col gap-4 md:gap-6">
            <Head title="Dashboard" />
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="col-span-1 md:col-span-2 xl:col-span-4">
                    <DashboardWidgets
                        widgets={props.widgets}
                        tabPanels={props.widgets_schema?.tab_panels}
                    />
                </div>
            </div>
        </div>
    );
}

FlatpackDashboard.layout = (page: React.ReactNode) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
