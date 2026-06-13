import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { DataTableRowDrawerPanel } from '@/components/table/data-table-row-drawer';
import type { FlatpackDataTableColumn } from '@/types/data-table';

describe('DataTableRowDrawerPanel', () => {
    const titleColumn: FlatpackDataTableColumn = {
        id: 'name',
        label: 'Name',
        type: 'text',
    };
    const columns: FlatpackDataTableColumn[] = [titleColumn];

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

    it('renders relation column through generic field pipeline', () => {
        const onRowReplace = vi.fn();
        const onOpenChange = vi.fn();
        const relationColumns: FlatpackDataTableColumn[] = [
            titleColumn,
            {
                id: 'user_id',
                label: 'User',
                type: 'relation',
                relation: 'user',
                relationName: 'name',
                relationValue: 'id',
                options: [{ value: '1', label: 'Ada' }],
            },
        ];
        render(
            <DataTableRowDrawerPanel
                open
                onOpenChange={onOpenChange}
                row={{ name: 'Post', user_id: '' }}
                rowId="__new__:1"
                schemaColumns={relationColumns}
                titleColumn={titleColumn}
                onRowReplace={onRowReplace}
            />,
        );
        expect(
            screen.queryByText(/Set options in the column/i),
        ).not.toBeInTheDocument();
    });

    it('renders drawer field errors for invalid row columns', () => {
        const onRowReplace = vi.fn();
        const onOpenChange = vi.fn();
        const relationColumns: FlatpackDataTableColumn[] = [
            titleColumn,
            {
                id: 'user_id',
                label: 'User',
                type: 'relation',
                relation: 'user',
                relationName: 'name',
                relationValue: 'id',
                options: [{ value: '1', label: 'Ada' }],
            },
        ];
        render(
            <DataTableRowDrawerPanel
                open
                onOpenChange={onOpenChange}
                row={{ name: 'Post', user_id: '' }}
                rowId="row-0"
                schemaColumns={relationColumns}
                titleColumn={titleColumn}
                onRowReplace={onRowReplace}
                columnValidationErrorsById={{
                    user_id: ['User id is required.'],
                }}
            />,
        );
        expect(screen.getByText('User id is required.')).toBeInTheDocument();
    });

    it('renders non-editable date columns as disabled in drawer', () => {
        const onRowReplace = vi.fn();
        const onOpenChange = vi.fn();
        const dateColumns: FlatpackDataTableColumn[] = [
            titleColumn,
            {
                id: 'created_at',
                label: 'Created At',
                type: 'date',
                editable: false,
            },
        ];
        render(
            <DataTableRowDrawerPanel
                open
                onOpenChange={onOpenChange}
                row={{ name: 'Post', created_at: '2026-04-30' }}
                rowId="row-0"
                schemaColumns={dateColumns}
                titleColumn={titleColumn}
                onRowReplace={onRowReplace}
            />,
        );
        expect(document.querySelector('[aria-disabled="true"]')).not.toBeNull();
    });

    it('renders non-editable relation columns as disabled in drawer', () => {
        const onRowReplace = vi.fn();
        const onOpenChange = vi.fn();
        const relationColumns: FlatpackDataTableColumn[] = [
            titleColumn,
            {
                id: 'user_id',
                label: 'User',
                type: 'relation',
                relation: 'user',
                relationName: 'name',
                relationValue: 'id',
                editable: false,
                options: [{ value: '1', label: 'Ada' }],
            },
        ];
        render(
            <DataTableRowDrawerPanel
                open
                onOpenChange={onOpenChange}
                row={{ name: 'Post', user_id: '1' }}
                rowId="row-1"
                schemaColumns={relationColumns}
                titleColumn={titleColumn}
                onRowReplace={onRowReplace}
            />,
        );
        expect(document.querySelector('[aria-disabled="true"]')).not.toBeNull();
    });
});
