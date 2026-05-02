import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

const renderWidgetByTypeMock = vi.fn((id: string, widget: { type: string }) => (
    <div
        data-testid="widget-stub"
        data-widget-id={id}
        data-widget-type={widget.type}
    >
        stub-{id}
    </div>
));

vi.mock('@/lib/widget-trigger', () => ({
    renderWidgetByType: (
        id: string,
        widget: { type: string },
    ): React.ReactNode => renderWidgetByTypeMock(id, widget),
    widgetGridSpanClass: (widget: { type: string }): string =>
        widget.type === 'chart' || widget.type === 'table'
            ? 'col-span-1 md:col-span-2 xl:col-span-4'
            : 'col-span-1',
}));

import { WidgetsRenderer } from '@/components/widgets/widgets-renderer';

function makeMetric(label: string): FlatpackMetricWidget {
    return {
        type: 'metric',
        provider: `provider_${label}`,
        label,
        value_format: { kind: 'number' },
        period: { kind: 'month', label: 'this month' },
    };
}

afterEach(() => {
    cleanup();
    renderWidgetByTypeMock.mockClear();
});

describe('WidgetsRenderer', () => {
    it('renders an empty grid when widgets are undefined', () => {
        const { container } = render(<WidgetsRenderer />);

        expect(container.querySelector('.grid')).toBeInTheDocument();
        expect(screen.queryAllByTestId('widget-stub')).toHaveLength(0);
        expect(renderWidgetByTypeMock).not.toHaveBeenCalled();
    });

    it('renders all widgets in a single grid when tabPanels are omitted', () => {
        render(
            <WidgetsRenderer
                widgets={{
                    a: makeMetric('A'),
                    b: makeMetric('B'),
                }}
            />,
        );

        expect(screen.getAllByTestId('widget-stub')).toHaveLength(2);
        expect(screen.getByText('stub-a')).toBeInTheDocument();
        expect(screen.getByText('stub-b')).toBeInTheDocument();
        expect(renderWidgetByTypeMock).toHaveBeenCalledTimes(2);
    });

    it('treats an empty tabPanels array like no tabs (flat grid)', () => {
        render(
            <WidgetsRenderer
                widgets={{ only: makeMetric('Only') }}
                tabPanels={[]}
            />,
        );

        expect(screen.queryByRole('tab')).not.toBeInTheDocument();
        expect(screen.getByTestId('widget-stub')).toHaveAttribute(
            'data-widget-id',
            'only',
        );
    });

    it('places tab-unassigned widgets above the tab list and assigned widgets inside tabs', () => {
        render(
            <WidgetsRenderer
                widgets={{
                    loose: makeMetric('Loose'),
                    in_tab: makeMetric('In tab'),
                }}
                tabPanels={[
                    {
                        id: 'main',
                        label: 'Main',
                        widget_ids: ['in_tab'],
                    },
                ]}
            />,
        );

        expect(screen.getByText('stub-loose')).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Main' })).toBeInTheDocument();
        expect(screen.getByText('stub-in_tab')).toBeInTheDocument();

        const grids = document.querySelectorAll('.grid.grid-cols-1');
        expect(grids.length).toBe(2);
    });

    it('omits missing widget ids from a panel without throwing', () => {
        render(
            <WidgetsRenderer
                widgets={{
                    present: makeMetric('Present'),
                }}
                tabPanels={[
                    {
                        id: 'p',
                        label: 'Panel',
                        widget_ids: ['missing', 'present'],
                    },
                ]}
            />,
        );

        expect(screen.getAllByTestId('widget-stub')).toHaveLength(1);
        expect(screen.getByTestId('widget-stub')).toHaveAttribute(
            'data-widget-id',
            'present',
        );
    });

    it('renders a registered menu icon on the tab trigger when icon is set', () => {
        render(
            <WidgetsRenderer
                widgets={{ w: makeMetric('W') }}
                tabPanels={[
                    {
                        id: 't',
                        label: 'Overview',
                        icon: 'bar-chart',
                        widget_ids: ['w'],
                    },
                ]}
            />,
        );

        const tab = screen.getByRole('tab', { name: 'Overview' });
        expect(tab.querySelector('[data-icon="inline-start"]')).toBeTruthy();
    });

    it('does not render an icon when icon is unknown', () => {
        render(
            <WidgetsRenderer
                widgets={{ w: makeMetric('W') }}
                tabPanels={[
                    {
                        id: 't',
                        label: 'Solo',
                        icon: 'not-a-real-menu-icon-key',
                        widget_ids: ['w'],
                    },
                ]}
            />,
        );

        const tab = screen.getByRole('tab', { name: 'Solo' });
        expect(tab.querySelector('[data-icon="inline-start"]')).toBeNull();
    });

    it('sets data-widget-id on each grid cell', () => {
        render(
            <WidgetsRenderer
                widgets={{
                    x: makeMetric('X'),
                    y: makeMetric('Y'),
                }}
            />,
        );

        expect(document.querySelector('[data-widget-id="x"]')).toBeTruthy();
        expect(document.querySelector('[data-widget-id="y"]')).toBeTruthy();
    });

    it('switches visible tab content when another tab is selected', async () => {
        const user = userEvent.setup();
        render(
            <WidgetsRenderer
                widgets={{
                    first: makeMetric('First'),
                    second: makeMetric('Second'),
                }}
                tabPanels={[
                    {
                        id: 'tab-a',
                        label: 'Alpha',
                        widget_ids: ['first'],
                    },
                    {
                        id: 'tab-b',
                        label: 'Beta',
                        widget_ids: ['second'],
                    },
                ]}
            />,
        );

        const tabBeta = screen.getByRole('tab', { name: 'Beta' });
        await user.click(tabBeta);

        await waitFor(() => {
            expect(tabBeta).toHaveAttribute('data-state', 'active');
        });
        expect(screen.getByText('stub-second')).toBeVisible();
    });

    it('resyncs active tab when tabPanels change and the previous id is gone', () => {
        const { rerender } = render(
            <WidgetsRenderer
                widgets={{ w: makeMetric('W') }}
                tabPanels={[
                    {
                        id: 'old',
                        label: 'Old',
                        widget_ids: ['w'],
                    },
                ]}
            />,
        );

        expect(screen.getByRole('tab', { name: 'Old' })).toHaveAttribute(
            'data-state',
            'active',
        );

        rerender(
            <WidgetsRenderer
                widgets={{ w: makeMetric('W') }}
                tabPanels={[
                    {
                        id: 'fresh',
                        label: 'Fresh',
                        widget_ids: ['w'],
                    },
                ]}
            />,
        );

        expect(screen.getByRole('tab', { name: 'Fresh' })).toHaveAttribute(
            'data-state',
            'active',
        );
    });
});
