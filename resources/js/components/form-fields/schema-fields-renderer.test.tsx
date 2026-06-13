import { fireEvent, render, screen } from '@testing-library/react';
import { lazy } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { SchemaFieldsRenderer } from '@/components/form-fields/schema-fields-renderer';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

describe('SchemaFieldsRenderer', () => {
    it('renders mapped field and emits serialized value', async () => {
        const onValueChange = vi.fn();
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'title',
                field: {
                    type: 'text',
                    label: 'Title',
                    placeholder: 'Type...',
                },
                value: '',
                onValueChange,
            },
        ];
        render(<SchemaFieldsRenderer entries={entries} />);
        const input = await screen.findByPlaceholderText('Type...');
        fireEvent.change(input, {
            target: { value: 'Hello' },
        });
        expect(onValueChange).toHaveBeenCalledWith('Hello');
    });

    it('does not allow extra props to override core value wiring', async () => {
        const onValueChange = vi.fn();
        const extraOnValueChange = vi.fn();
        let capturedProps: Record<string, unknown> = {};
        const StubField = lazy(async () => ({
            default: (props: Record<string, unknown>) => {
                capturedProps = props;
                return (
                    <button
                        type="button"
                        onClick={() => {
                            const handler = props.onValueChange as
                                | ((value: unknown) => void)
                                | undefined;
                            handler?.('Serialized');
                        }}
                    >
                        Emit
                    </button>
                );
            },
        }));

        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'title',
                field: {
                    type: 'text',
                    label: 'Title',
                },
                value: '',
                onValueChange,
                extraComponentProps: {
                    id: 'hijacked-id',
                    onValueChange: extraOnValueChange,
                },
            },
        ];

        render(
            <SchemaFieldsRenderer
                entries={entries}
                fieldComponents={{ title: StubField }}
            />,
        );

        fireEvent.click(await screen.findByRole('button', { name: 'Emit' }));

        expect(capturedProps.id).toBe('title');
        expect(onValueChange).toHaveBeenCalledWith('Serialized');
        expect(extraOnValueChange).not.toHaveBeenCalled();
    });

    it('uses aligned two-column label grid when every field uses showLabel inline', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'a',
                field: {
                    type: 'text',
                    label: 'Alpha',
                    showLabel: 'inline',
                },
                value: '',
                onValueChange: vi.fn(),
            },
            {
                id: 'b',
                field: {
                    type: 'text',
                    label: 'Beta label',
                    showLabel: 'inline',
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        expect(
            container.querySelector('[data-slot="inline-fieldset-label-grid"]'),
        ).not.toBeNull();
    });

    it('does not use aligned label grid when showLabel is mixed', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'a',
                field: {
                    type: 'text',
                    label: 'A',
                    showLabel: 'inline',
                },
                value: '',
                onValueChange: vi.fn(),
            },
            {
                id: 'b',
                field: {
                    type: 'text',
                    label: 'B',
                    showLabel: 'stacked',
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        expect(
            container.querySelector('[data-slot="inline-fieldset-label-grid"]'),
        ).toBeNull();
    });

    it('applies grid span classes from field.span', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'status',
                field: {
                    type: 'text',
                    label: 'Status',
                    span: 'half',
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        const spanEl = container.querySelector('[class*="lg:col-span-2"]');
        expect(spanEl).not.toBeNull();
    });

    it('wraps consecutive shared fieldset entries in a card', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'a',
                field: {
                    type: 'text',
                    label: 'A',
                    fieldset: 'Details',
                },
                value: '',
                onValueChange: vi.fn(),
            },
            {
                id: 'b',
                field: {
                    type: 'text',
                    label: 'B',
                    fieldset: 'Details',
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        render(<SchemaFieldsRenderer entries={entries} spanContext="page" />);
        expect(await screen.findByText('Details')).toBeInTheDocument();
        expect(await screen.findByLabelText('A')).toBeInTheDocument();
        expect(await screen.findByLabelText('B')).toBeInTheDocument();
    });

    it('renders fieldset object with icon in card header', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 's',
                field: {
                    type: 'text',
                    label: 'Code',
                    fieldset: { label: 'Status', icon: 'clock' },
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        expect(
            container.querySelector('[data-slot="card-title"]'),
        ).toHaveTextContent('Status');
        expect(await screen.findByLabelText('Code')).toBeInTheDocument();
        expect(
            container.querySelector('[data-icon="inline-start"]'),
        ).not.toBeNull();
    });

    it('renders minimal fieldset without card chrome', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'minimal-field',
                field: {
                    type: 'text',
                    label: 'Minimal input',
                    fieldset: { label: 'Light', variant: 'minimal' },
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        expect(container.querySelector('[data-slot="card-title"]')).toBeNull();
        expect(await screen.findByText('Light')).toBeInTheDocument();
        expect(screen.getByLabelText('Minimal input')).toBeInTheDocument();
    });

    it('renders collapsible fieldset when collapsed is set', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'f1',
                field: {
                    type: 'text',
                    label: 'Field one',
                    fieldset: { label: 'Section', collapsed: false },
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        expect(
            container.querySelector('[data-slot="collapsible"]'),
        ).not.toBeNull();
        expect(await screen.findByText('Section')).toBeInTheDocument();
    });

    it('renders none fieldset with screen-reader legend only', async () => {
        const entries: SchemaFieldRenderEntry[] = [
            {
                id: 'none-field',
                field: {
                    type: 'text',
                    label: 'None input',
                    fieldset: { label: 'Hidden section', variant: 'none' },
                },
                value: '',
                onValueChange: vi.fn(),
            },
        ];
        const { container } = render(
            <SchemaFieldsRenderer entries={entries} spanContext="page" />,
        );
        const legend = container.querySelector('fieldset legend.sr-only');
        expect(legend).not.toBeNull();
        expect(legend?.textContent).toBe('Hidden section');
        expect(screen.getByLabelText('None input')).toBeInTheDocument();
    });
});
