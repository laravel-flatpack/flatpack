import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

const widgetsRendererSpy = vi.fn();

vi.mock('@/components/widgets/widgets-renderer', () => ({
    WidgetsRenderer: (props: {
        widgets?: Record<string, unknown>;
        tabPanels?: unknown[];
    }) => {
        widgetsRendererSpy(props);
        return <div data-testid="widgets-renderer-mock" />;
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
}));

import FlatpackDashboard from '@/pages/dashboard';

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
    widgetsRendererSpy.mockClear();
});

describe('FlatpackDashboard', () => {
    it('sets the document title to Dashboard', () => {
        render(<FlatpackDashboard widgets={{}} />);

        expect(document.querySelector('title')?.textContent).toBe('Dashboard');
    });

    it('passes top-level widgets into WidgetsRenderer', () => {
        const widgets = { a: makeMetric('A') };
        render(<FlatpackDashboard widgets={widgets} />);

        expect(widgetsRendererSpy).toHaveBeenCalledWith(
            expect.objectContaining({
                widgets,
            }),
        );
        expect(screen.getByTestId('widgets-renderer-mock')).toBeInTheDocument();
    });

    it('passes schema.tab_panels as tabPanels', () => {
        const widgets = { w: makeMetric('W') };
        const tabPanels = [
            {
                id: 'overview',
                label: 'Overview',
                widget_ids: ['w'],
            },
        ];

        render(
            <FlatpackDashboard
                widgets={widgets}
                schema={{
                    widgets,
                    tab_panels: tabPanels,
                }}
            />,
        );

        expect(widgetsRendererSpy).toHaveBeenCalledWith(
            expect.objectContaining({
                widgets,
                tabPanels,
            }),
        );
    });

    it('passes undefined tabPanels when schema is absent', () => {
        render(<FlatpackDashboard widgets={{ x: makeMetric('X') }} />);

        expect(widgetsRendererSpy).toHaveBeenCalledWith(
            expect.objectContaining({
                tabPanels: undefined,
            }),
        );
    });

    it('passes undefined tabPanels when schema has no tab_panels', () => {
        render(
            <FlatpackDashboard
                widgets={{ x: makeMetric('X') }}
                schema={{ widgets: { x: makeMetric('X') } }}
            />,
        );

        expect(widgetsRendererSpy).toHaveBeenCalledWith(
            expect.objectContaining({
                tabPanels: undefined,
            }),
        );
    });

    it('passes undefined tabPanels when schema is null', () => {
        render(
            <FlatpackDashboard
                widgets={{ x: makeMetric('X') }}
                schema={null}
            />,
        );

        expect(widgetsRendererSpy).toHaveBeenCalledWith(
            expect.objectContaining({
                tabPanels: undefined,
            }),
        );
    });
});
