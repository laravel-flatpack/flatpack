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
        const wrapper = container.firstChild as HTMLElement;
        expect(wrapper.className).toContain('lg:col-span-2');
    });
});
