import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { MetricWidget } from '@/components/widgets/metric';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

describe('MetricWidget', () => {
    it('renders metric label, value, trend, and description', () => {
        const widget: FlatpackMetricWidget = {
            type: 'metric',
            provider: 'total_revenue',
            label: 'Total Revenue',
            description: 'Revenue trend for the last 6 months',
            value_format: {
                kind: 'currency',
                currency: 'USD',
            },
            period: {
                kind: 'month',
                label: 'this month',
            },
            trend: {
                precision: 1,
            },
            data: {
                value: 1250,
                trend: {
                    direction: 'up',
                    percent: 12.5,
                    comment: 'Trending up this month',
                },
            },
        };

        render(<MetricWidget widget={widget} />);

        expect(screen.getByText('Total Revenue')).toBeInTheDocument();
        expect(screen.getByText(/\$1,250/)).toBeInTheDocument();
        expect(screen.getByText('+12.5%')).toBeInTheDocument();
        expect(screen.getByText('Trending up this month')).toBeInTheDocument();
        expect(
            screen.getByText('Revenue trend for the last 6 months'),
        ).toBeInTheDocument();
    });
});
