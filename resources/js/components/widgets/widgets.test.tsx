import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { CardWidget } from '@/components/widgets/card';
import { ChartWidget } from '@/components/widgets/chart';
import { MetricWidget } from '@/components/widgets/metric';
import { StatusWidget } from '@/components/widgets/status';
import { TableWidget } from '@/components/widgets/table-widget';
import type {
    FlatpackCardWidget,
    FlatpackChartWidget,
    FlatpackMetricWidget,
    FlatpackStatusWidget,
    FlatpackTableWidget,
} from '@/types/widgets-composition';

type DataTableProps = Record<string, unknown>;

let lastDataTableProps: DataTableProps | null = null;
let lastDataTableId: string | null = null;
let lastDataTableData: unknown[] | null = null;
let lastDataTableBulkActions: unknown[] | null = null;

afterEach(() => {
    cleanup();
});

vi.mock('@/hooks/use-mobile', () => ({
    useIsMobile: () => false,
}));

vi.mock('@/components/table/data-table', () => ({
    DataTable: (props: DataTableProps) => {
        lastDataTableProps = props;
        lastDataTableId = typeof props.id === 'string' ? props.id : null;
        lastDataTableData = Array.isArray(props.data) ? props.data : null;
        lastDataTableBulkActions = Array.isArray(props.bulkActions)
            ? props.bulkActions
            : null;
        return <div data-testid="table-widget-data-table" />;
    },
}));

function makeMetricWidget(
    overrides: Partial<FlatpackMetricWidget> = {},
): FlatpackMetricWidget {
    return {
        type: 'metric',
        provider: 'total_revenue',
        label: 'Total Revenue',
        description: 'Revenue trend for the last 6 months',
        value_format: { kind: 'currency', currency: 'USD' },
        period: { kind: 'month', label: 'this month' },
        trend: { precision: 1 },
        data: {
            value: 1250,
            trend: {
                direction: 'up',
                percent: 12.5,
                comment: 'Trending up this month',
            },
        },
        ...overrides,
    };
}

function makeCardWidget(
    overrides: Partial<FlatpackCardWidget> = {},
): FlatpackCardWidget {
    return {
        type: 'card',
        provider: 'orders_card',
        label: 'Orders',
        data: {
            value: 42,
            context: 'Compared to last week',
            footer: 'Last update at 10:00',
        },
        ...overrides,
    };
}

function makeStatusWidget(
    overrides: Partial<FlatpackStatusWidget> = {},
): FlatpackStatusWidget {
    return {
        type: 'status',
        provider: 'api_status',
        label: 'API',
        description: 'Main API status',
        data: {
            status: 'success',
            value: 'Operational',
            context: 'No incidents detected',
        },
        ...overrides,
    };
}

function makeChartWidget(
    overrides: Partial<FlatpackChartWidget> = {},
): FlatpackChartWidget {
    return {
        type: 'chart',
        provider: 'sales_chart',
        label: 'Sales',
        description: 'Monthly sales',
        chart: {
            x_key: 'date',
            variant: 'area',
            series: [{ key: 'total', label: 'Total' }],
        },
        data: {
            points: [{ date: '2026-01-01', total: 100 }],
        },
        ...overrides,
    };
}

function makeTableWidget(
    overrides: Partial<FlatpackTableWidget> = {},
): FlatpackTableWidget {
    return {
        type: 'table',
        label: 'Recent Orders',
        columns: {
            id: { id: 'id', label: 'Id', type: 'text' },
        },
        data: {
            rows: [{ id: 1 }],
        },
        ...overrides,
    };
}

describe('widgets basic rendering', () => {
    it('renders MetricWidget headline content', () => {
        render(<MetricWidget widget={makeMetricWidget()} />);

        expect(screen.getByText('Total Revenue')).toBeInTheDocument();
        expect(screen.getByText(/\$1,250/)).toBeInTheDocument();
        expect(screen.getByText('+12.5%')).toBeInTheDocument();
    });

    it('renders CardWidget value and supporting copy', () => {
        render(<CardWidget widget={makeCardWidget()} />);

        expect(screen.getByText('Orders')).toBeInTheDocument();
        expect(screen.getByText('42')).toBeInTheDocument();
        expect(screen.getByText('Compared to last week')).toBeInTheDocument();
    });

    it('renders StatusWidget status label and value', () => {
        render(<StatusWidget widget={makeStatusWidget()} />);

        expect(screen.getByText('API')).toBeInTheDocument();
        expect(screen.getByText('Success')).toBeInTheDocument();
        expect(screen.getByText('Operational')).toBeInTheDocument();
    });

    it('renders ChartWidget empty state when series are missing', () => {
        const widget = makeChartWidget({
            chart: {
                x_key: 'date',
                variant: 'area',
                series: [],
            },
        });

        render(<ChartWidget widget={widget} />);

        expect(screen.getByText('Sales')).toBeInTheDocument();
        expect(
            screen.getByText('Chart series are not configured.'),
        ).toBeInTheDocument();
    });

    it('renders ChartWidget empty state when points are missing', () => {
        const widget = makeChartWidget({
            data: {
                points: [],
            },
        });

        render(<ChartWidget widget={widget} />);

        expect(screen.getByText('Sales')).toBeInTheDocument();
        expect(
            screen.getByText('No data available for this chart.'),
        ).toBeInTheDocument();
    });

    it('renders TableWidget through DataTable adapter', () => {
        lastDataTableProps = null;
        lastDataTableId = null;
        lastDataTableData = null;

        render(
            <TableWidget widgetId="Orders Widget" widget={makeTableWidget()} />,
        );

        expect(
            screen.getByTestId('table-widget-data-table'),
        ).toBeInTheDocument();
        expect(lastDataTableProps).not.toBeNull();
        expect(lastDataTableId).toBe('widget-table-orders-widget');
        expect(lastDataTableData).toEqual([{ id: 1 }]);
    });

    it('passes widget bulk_actions to DataTable for model-backed tables', () => {
        lastDataTableProps = null;
        lastDataTableBulkActions = null;

        render(
            <TableWidget
                widgetId="Comments Widget"
                widget={makeTableWidget({
                    model: 'App\\Models\\Comment',
                    bulk_actions: [
                        {
                            id: 'delete',
                            label: 'Delete',
                            action: 'delete',
                            variant: 'destructive',
                        },
                    ],
                })}
            />,
        );

        expect(lastDataTableProps).not.toBeNull();
        expect(lastDataTableBulkActions).toEqual([
            {
                id: 'delete',
                label: 'Delete',
                action: 'delete',
                variant: 'destructive',
            },
        ]);
    });
});
