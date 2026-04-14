import type { FlatpackDataTableColumn } from '@/types/data-table';

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
