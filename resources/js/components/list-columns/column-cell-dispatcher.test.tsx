import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ColumnCellDispatcher } from '@/components/list-columns/column-cell-dispatcher';
import type { ListColumnRenderProps } from '@/types/list-columns';

vi.mock('@/components/table/data-table-row-drawer', () => ({
    DataTableRowDetailDrawer: () => <div data-testid="detail-drawer-cell" />,
}));

vi.mock('@/components/list-columns/relation-cell', () => ({
    RelationCell: () => <div data-testid="relation-cell" />,
}));

vi.mock('@/components/list-columns/badge-cell', () => ({
    BadgeCell: () => <div data-testid="badge-cell" />,
}));

vi.mock('@/components/list-columns/select-cell', () => ({
    SelectCell: () => <div data-testid="select-cell" />,
}));

vi.mock('@/components/list-columns/date-cell', () => ({
    DateCell: () => <div data-testid="date-cell" />,
}));

vi.mock('@/components/list-columns/text-cell', () => ({
    EditableTextCell: () => <div data-testid="editable-text-cell" />,
}));

const baseProps: ListColumnRenderProps = {
    column: { id: 'name', label: 'Name', type: 'text' },
    value: 'alpha',
    rowId: 'r1',
    row: { id: 'r1', name: 'alpha' },
    schemaColumns: [{ id: 'name', label: 'Name', type: 'text' }],
    onCellChange: vi.fn(),
    inlineCellEdit: true,
};

describe('ColumnCellDispatcher', () => {
    afterEach(() => {
        cleanup();
    });

    it('prioritizes detail drawer when enabled', () => {
        render(
            <ColumnCellDispatcher
                {...baseProps}
                column={{
                    id: 'name',
                    label: 'Name',
                    type: 'text',
                    detailDrawer: true,
                }}
                onRowReplace={vi.fn()}
            />,
        );

        expect(screen.getByTestId('detail-drawer-cell')).toBeInTheDocument();
        expect(
            screen.queryByTestId('editable-text-cell'),
        ).not.toBeInTheDocument();
    });

    it('routes relation type when relation metadata is complete', () => {
        render(
            <ColumnCellDispatcher
                {...baseProps}
                column={{
                    id: 'author',
                    label: 'Author',
                    type: 'relation',
                    relation: 'author',
                    relationName: 'name',
                    relationValue: 'id',
                }}
            />,
        );

        expect(screen.getByTestId('relation-cell')).toBeInTheDocument();
    });

    it('routes badge and status to badge cell', () => {
        const { rerender } = render(
            <ColumnCellDispatcher
                {...baseProps}
                column={{ id: 'state', label: 'State', type: 'badge' }}
            />,
        );

        expect(screen.getByTestId('badge-cell')).toBeInTheDocument();

        rerender(
            <ColumnCellDispatcher
                {...baseProps}
                column={{ id: 'state', label: 'State', type: 'status' }}
            />,
        );

        expect(screen.getByTestId('badge-cell')).toBeInTheDocument();
    });

    it('uses editable text renderer for editable fallback types', () => {
        render(
            <ColumnCellDispatcher
                {...baseProps}
                column={{
                    id: 'name',
                    label: 'Name',
                    type: 'text',
                    editable: true,
                }}
            />,
        );
        expect(screen.getByTestId('editable-text-cell')).toBeInTheDocument();
    });

    it('uses readonly fallback when inline editing is disabled', () => {
        render(<ColumnCellDispatcher {...baseProps} inlineCellEdit={false} />);

        expect(screen.getByText('alpha')).toBeInTheDocument();
        expect(
            screen.queryByTestId('editable-text-cell'),
        ).not.toBeInTheDocument();
    });
});
