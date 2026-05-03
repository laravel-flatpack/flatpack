import {
    cleanup,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { SchemaDocument, SchemaPageProps } from '@/types/schema';

const { usePage } = vi.hoisted(() => ({
    usePage: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => usePage(),
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => (
        <a href={href}>{children}</a>
    ),
}));

import SchemaPage from '@/pages/docs/schema';

function buildDocument(
    overrides: Partial<SchemaDocument> = {},
): SchemaDocument {
    return {
        id: 'form',
        title: 'Form composition schema',
        description: 'Normalized documentation view.',
        meta: {
            propertyCount: 1,
            definitionCount: 2,
        },
        root: {
            key: 'root',
            title: 'Composition root',
            description: 'Top-level shape.',
            type: 'object',
            required: ['name'],
            properties: [
                {
                    name: 'name',
                    required: true,
                    description: 'Record title.',
                    type: 'string',
                    ref: null,
                    options: [],
                    const: '',
                    default: '',
                    validation: { maxLength: '255' },
                    compositionRules: {},
                },
            ],
            options: ['draft', 'published'],
            const: '',
            validation: { maxProperties: '50' },
            compositionRules: {},
            ref: null,
        },
        definitions: [
            {
                key: 'fieldDefinition',
                title: null,
                description: 'Defines one field block.',
                type: 'object',
                required: ['type'],
                properties: [
                    {
                        name: 'type',
                        required: true,
                        description: null,
                        type: 'string',
                        ref: null,
                        options: ['text', 'select'],
                        const: '',
                        default: '',
                        validation: {},
                        compositionRules: {},
                    },
                ],
                options: [],
                const: '',
                validation: {},
                compositionRules: {},
                ref: null,
            },
            {
                key: 'emptyDef',
                title: null,
                description: 'No nested properties here.',
                type: 'object',
                required: [],
                properties: [],
                options: [],
                const: '',
                validation: {},
                compositionRules: {},
                ref: null,
            },
        ],
        raw: { $schema: 'https://json-schema.org/draft/2020-12/schema' },
        ...overrides,
    };
}

function mockPageProps(
    overrides: Partial<SchemaPageProps> = {},
): SchemaPageProps {
    return {
        schemaType: 'form',
        query: {},
        document: buildDocument(),
        ...overrides,
    };
}

describe('SchemaPage', () => {
    beforeEach(() => {
        usePage.mockReset();
    });

    afterEach(() => {
        cleanup();
    });

    it('omits the description paragraph when document.description is empty', () => {
        usePage.mockReturnValue({
            props: mockPageProps({
                document: buildDocument({ description: null }),
            }),
        });

        render(<SchemaPage />);

        expect(
            screen.queryByText('Normalized documentation view.'),
        ).not.toBeInTheDocument();
    });

    it('renders head title, summary badges, and the overview root section', () => {
        usePage.mockReturnValue({ props: mockPageProps() });

        render(<SchemaPage />);

        expect(globalThis.document.querySelector('title')?.textContent).toBe(
            'Schema - form',
        );
        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Form composition schema',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('1 root properties')).toBeInTheDocument();
        expect(screen.getByText('2 definitions')).toBeInTheDocument();

        const overview = screen.getByRole('tabpanel', { name: 'Overview' });

        expect(
            within(overview).getByRole('heading', { name: 'Composition root' }),
        ).toBeInTheDocument();
        expect(
            within(overview).getByText('Top-level shape.'),
        ).toBeInTheDocument();
        expect(
            within(overview).getByText('1 required keys'),
        ).toBeInTheDocument();
        expect(
            within(overview).getByText('2 available options'),
        ).toBeInTheDocument();
        expect(
            within(overview).getByText('maxProperties: 50'),
        ).toBeInTheDocument();
        expect(within(overview).getByText('draft')).toBeInTheDocument();
        expect(within(overview).getByText('published')).toBeInTheDocument();

        expect(within(overview).getByText('name')).toBeInTheDocument();
        expect(within(overview).getByText('Record title.')).toBeInTheDocument();
        expect(
            within(overview).getByText('maxLength: 255'),
        ).toBeInTheDocument();
    });

    it('shows definition sections and no nested properties copy when empty', async () => {
        const user = userEvent.setup();
        usePage.mockReturnValue({ props: mockPageProps() });

        render(<SchemaPage />);

        await user.click(screen.getByRole('tab', { name: 'Definitions' }));

        const definitions = screen.getByRole('tabpanel', {
            name: 'Definitions',
        });

        expect(
            within(definitions).getByText('#/$defs/fieldDefinition'),
        ).toBeInTheDocument();
        expect(
            within(definitions).getAllByText('Defines one field block.'),
        ).toHaveLength(2);
        expect(
            within(definitions).getByText('text', { exact: true }),
        ).toBeInTheDocument();
        expect(
            within(definitions).getByText('select', { exact: true }),
        ).toBeInTheDocument();

        await user.click(within(definitions).getByText('#/$defs/emptyDef'));

        expect(
            within(definitions).getByText('No nested properties.'),
        ).toBeInTheDocument();
    });

    it('shows raw JSON when the Raw JSON tab is selected', async () => {
        const user = userEvent.setup();
        const rawPayload = { sample: true, nested: { n: 1 } };
        usePage.mockReturnValue({
            props: mockPageProps({
                document: buildDocument({ raw: rawPayload }),
            }),
        });

        render(<SchemaPage />);

        await user.click(screen.getByRole('tab', { name: 'Raw JSON' }));

        await waitFor(() => {
            expect(screen.getByText(/"sample"/)).toBeInTheDocument();
        });
    });
});
