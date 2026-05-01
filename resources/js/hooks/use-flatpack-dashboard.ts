import { useFlatpackPage } from '@/hooks/use-flatpack-page';
import type { FlatpackDashboardPageProps } from '@/types/pages/flatpack';

export function useFlatpackDashboard(props: FlatpackDashboardPageProps) {
    useFlatpackPage(props);

    return {
        widgets: props.widgets,
        tabPanels: props.schema?.tab_panels,
    };
}
