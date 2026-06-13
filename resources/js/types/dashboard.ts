export type DashboardTableRow = Record<string, unknown> & {
    id: number;
    header: string;
    type: string;
    status: string;
    target: string;
    limit: string;
};
