import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { DataTableRowDrawerPanel } from '@/components/table/data-table-row-drawer';
import type { FlatpackDataTableColumn } from '@/types/data-table';

describe('DataTableRowDrawerPanel', () => {
    const columns: FlatpackDataTableColumn[] = [
        { id: 'name', label: 'Name', type: 'text' },
    ];
    const titleColumn = columns[0]!;

    afterEach(() => {
        cleanup();
    });

    beforeAll(() => {
        if (!('setPointerCapture' in Element.prototype)) {
            Object.defineProperty(Element.prototype, 'setPointerCapture', {
                value: () => {},
            });
        }
        if (!('releasePointerCapture' in Element.prototype)) {
            Object.defineProperty(Element.prototype, 'releasePointerCapture', {
                value: () => {},
            });
        }
    });

    it('renders custom attach body when bodyVariant is attachExisting and render is set', () => {
        const onRowReplace = vi.fn();
        const onOpenChange = vi.fn();
        render(
            <DataTableRowDrawerPanel
                open
                onOpenChange={onOpenChange}
                row={{ name: '' }}
                rowId="__new__:1"
                schemaColumns={columns}
                titleColumn={titleColumn}
                onRowReplace={onRowReplace}
                bodyVariant="attachExisting"
                renderAttachBody={() => <p>Attach body slot</p>}
            />,
        );
        expect(screen.getByText('Attach body slot')).toBeInTheDocument();
    });
});
