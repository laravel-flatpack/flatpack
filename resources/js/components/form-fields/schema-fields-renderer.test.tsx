import { fireEvent, render, screen } from '@testing-library/react';
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
});
