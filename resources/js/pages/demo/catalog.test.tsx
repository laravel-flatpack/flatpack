import {
    cleanup,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    DemoCatalogDocument,
    DemoComponentCatalogEntry,
    DemoComponentsCatalogId,
} from '@/types/demo';

const { routeMock, usePage } = vi.hoisted(() => ({
    routeMock: vi.fn(() => '/components'),
    usePage: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => usePage(),
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({
        children,
        href,
        className,
    }: {
        children: React.ReactNode;
        href: string;
        className?: string;
    }) => (
        <a href={href} className={className}>
            {children}
        </a>
    ),
}));

vi.mock('@/lib/route', () => ({
    route: routeMock,
}));

vi.mock('@/lib/form', () => ({
    loadField: vi.fn(() =>
        React.lazy(() =>
            Promise.resolve({
                default: (props: { onValueChange?: (v: unknown) => void }) => (
                    <button
                        type="button"
                        data-testid="demo-mock-field"
                        onClick={() => props.onValueChange?.('from-mock')}
                    >
                        field
                    </button>
                ),
            }),
        ),
    ),
}));

import DemoPage from '@/pages/demo/catalog';

function textEntry(
    overrides: Partial<DemoComponentCatalogEntry> = {},
): DemoComponentCatalogEntry {
    return {
        id: 'e-text',
        title: 'Text field',
        description: 'desc',
        value: null,
        showValue: true,
        props: { type: 'text', label: 'L', placeholder: 'p' },
        ...overrides,
    };
}

function buildDocument(
    id: DemoComponentsCatalogId,
    fields: DemoComponentCatalogEntry[],
): DemoCatalogDocument {
    return {
        id,
        title:
            id === 'fields'
                ? 'Form Fields'
                : id === 'widgets'
                  ? 'Widgets'
                  : 'Components',
        description: null,
        meta: { fieldCount: fields.length, widgetCount: 0 },
        fields: id === 'widgets' ? [] : fields,
        widgets: [],
    };
}

describe('DemoPage', () => {
    beforeEach(() => {
        usePage.mockReset();
    });

    afterEach(() => {
        cleanup();
    });

    it('shows unknown component message for invalid type', () => {
        usePage.mockReturnValue({
            props: {
                catalogId: 'all',
                query: {},
                document: buildDocument('all', [textEntry()]),
            },
            url: '/components?type=not-real',
        });

        render(<DemoPage />);

        expect(screen.getByText(/Unknown component type/)).toBeInTheDocument();
        expect(screen.getByText('not-real')).toBeInTheDocument();
    });

    it('lists all components when no selector is set', async () => {
        usePage.mockReturnValue({
            props: {
                catalogId: 'all',
                query: {},
                document: buildDocument('all', [
                    textEntry({ title: 'Alpha' }),
                    textEntry({
                        id: 'e-ta',
                        title: 'Beta',
                        props: { type: 'textarea', label: 'L' },
                    }),
                ]),
            },
            url: '/components',
        });

        render(<DemoPage />);

        expect(
            await screen.findByRole(
                'heading',
                {
                    level: 1,
                    name: 'Components',
                },
                { timeout: 15000 },
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Alpha' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Beta' }),
        ).toBeInTheDocument();
    }, 20000);

    it('renders single field and live value panel when type is set', async () => {
        const user = userEvent.setup();
        usePage.mockReturnValue({
            props: {
                catalogId: 'all',
                query: {},
                document: buildDocument('all', [textEntry()]),
            },
            url: '/components?type=text',
        });

        render(<DemoPage />);

        await waitFor(() => {
            expect(
                document.querySelector('[data-slot="demo-components-single"]'),
            ).not.toBeNull();
        });
        const root = document.querySelector(
            '[data-slot="demo-components-single"]',
        ) as HTMLElement;
        expect(root).toHaveAttribute('data-demo-component', 'e-text');

        const scoped = within(root);
        await waitFor(() => {
            expect(scoped.getByTestId('demo-mock-field')).toBeInTheDocument();
        });

        expect(scoped.getByText('Live Value')).toBeInTheDocument();

        await user.click(scoped.getByTestId('demo-mock-field'));

        await waitFor(() => {
            const pre = scoped.getByText(/from-mock/);
            expect(pre.tagName).toBe('PRE');
        });
    });

    it('hides live value when showValue=false in query', async () => {
        usePage.mockReturnValue({
            props: {
                catalogId: 'all',
                query: {},
                document: buildDocument('all', [textEntry()]),
            },
            url: '/components?type=text&showValue=false',
        });

        render(<DemoPage />);

        await waitFor(() => {
            expect(
                document.querySelector('[data-slot="demo-components-single"]'),
            ).toBeInstanceOf(HTMLElement);
        });
        const root = document.querySelector(
            '[data-slot="demo-components-single"]',
        ) as HTMLElement;
        const scoped = within(root);
        await waitFor(() => {
            expect(scoped.getByTestId('demo-mock-field')).toBeInTheDocument();
        });

        expect(scoped.queryByText('Live Value')).not.toBeInTheDocument();
    });
});
