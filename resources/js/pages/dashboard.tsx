import { Head } from '@inertiajs/react';
import { WidgetsRenderer } from '@/components/widgets/widgets-renderer';
import { useFlatpackDashboard } from '@/hooks/use-flatpack-dashboard';
import FlatpackLayout from '@/layouts/flatpack-layout';
import type { FlatpackDashboardPageProps } from '@/types/pages/flatpack';

export default function FlatpackDashboard(props: FlatpackDashboardPageProps) {
    const { widgets, tabPanels } = useFlatpackDashboard(props);

    return (
        <div className="flex flex-col gap-4 md:gap-6">
            <Head title="Dashboard" />
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="col-span-1 md:col-span-2 xl:col-span-4">
                    <WidgetsRenderer widgets={widgets} tabPanels={tabPanels} />
                </div>
            </div>
        </div>
    );
}

FlatpackDashboard.layout = (page: React.ReactNode) => (
    <FlatpackLayout>{page}</FlatpackLayout>
);
