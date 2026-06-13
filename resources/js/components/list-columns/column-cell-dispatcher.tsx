import { BadgeCell } from '@/components/list-columns/badge-cell';
import { buildListColumnSharedContext } from '@/components/list-columns/column-cell-context';
import { DetailDrawerColumnCell } from '@/components/list-columns/column-cell-detail-drawer';
import { ReadOnlyFallbackColumnCell } from '@/components/list-columns/column-cell-readonly-fallback';
import { DateCell } from '@/components/list-columns/date-cell';
import { RelationCell } from '@/components/list-columns/relation-cell';
import { SelectCell } from '@/components/list-columns/select-cell';
import { EditableTextCell } from '@/components/list-columns/text-cell';
import { selectColumnOptions } from '@/lib/data-table-utils';
import type { ListColumnRenderProps } from '@/types/list-columns';
import { ImageCell } from './image-cell';

export function ColumnCellDispatcher(props: ListColumnRenderProps) {
    const { column, row, value } = props;

    const drawerCell = DetailDrawerColumnCell(props);
    if (drawerCell != null) {
        return drawerCell;
    }

    const { editable, controlId, commit } = buildListColumnSharedContext(props);

    if (
        column.type === 'relation' &&
        column.relation &&
        column.relationName &&
        column.relationValue
    ) {
        return <RelationCell row={row} column={column} />;
    }

    if (column.type === 'badge' || column.type === 'status') {
        return (
            <BadgeCell
                column={column}
                value={value}
                truncate={column.truncate}
            />
        );
    }

    if (column.type === 'select' && selectColumnOptions(column).length > 0) {
        return (
            <SelectCell
                column={column}
                value={value}
                editable={editable}
                controlId={controlId}
                commit={commit}
            />
        );
    }

    if (column.type === 'date') {
        return (
            <DateCell
                column={column}
                value={value}
                editable={editable}
                controlId={controlId}
                commit={commit}
            />
        );
    }
    if (column.type === 'image') {
        return <ImageCell column={column} value={value} />;
    }

    if (editable) {
        return (
            <EditableTextCell
                value={value}
                commit={commit}
                ariaLabel={column.label}
                controlId={controlId}
            />
        );
    }

    return <ReadOnlyFallbackColumnCell column={column} value={value} />;
}
