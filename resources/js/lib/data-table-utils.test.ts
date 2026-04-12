import { describe, expect, it } from 'vitest';
import {
    cellControlDomId,
    columnEditableInDrawer,
    dateInputSegment,
    formatCellValue,
    interpolateRowPlaceholders,
    mergeCommittedDate,
    reindexReorderColumn,
    stableRowId,
} from '@/lib/data-table-utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

describe('stableRowId', () => {
    it('stringifies numeric id', () => {
        expect(stableRowId({ id: 42 }, 0)).toBe('42');
    });

    it('keeps string id', () => {
        expect(stableRowId({ id: 'abc-1' }, 3)).toBe('abc-1');
    });

    it('uses row index when id is null', () => {
        expect(stableRowId({ id: null }, 2)).toBe('row-2');
    });

    it('uses row index when id is undefined', () => {
        expect(stableRowId({}, 5)).toBe('row-5');
    });

    it('uses id 0 as stable id', () => {
        expect(stableRowId({ id: 0 }, 99)).toBe('0');
    });
});

describe('reindexReorderColumn', () => {
    it('sets reorder key to 1-based index', () => {
        const rows = [{ id: 'a' }, { id: 'b' }];
        const out = reindexReorderColumn(rows, 'sort_order');
        expect(out).toEqual([
            { id: 'a', sort_order: 1 },
            { id: 'b', sort_order: 2 },
        ]);
    });

    it('does not mutate original rows', () => {
        const rows = [{ id: 1, sort_order: 99 }];
        reindexReorderColumn(rows, 'sort_order');
        expect(rows[0]).toEqual({ id: 1, sort_order: 99 });
    });
});

describe('interpolateRowPlaceholders', () => {
    it('replaces single placeholder', () => {
        expect(interpolateRowPlaceholders('/users/{id}', { id: 7 })).toBe(
            '/users/7',
        );
    });

    it('trims key inside braces', () => {
        expect(interpolateRowPlaceholders('/{ id }', { id: 'x' })).toBe('/x');
    });

    it('uses empty string for missing or null values', () => {
        expect(interpolateRowPlaceholders('{a}{b}', { a: null })).toBe('');
        expect(interpolateRowPlaceholders('{x}', {})).toBe('');
    });

    it('stringifies numbers', () => {
        expect(interpolateRowPlaceholders('{n}', { n: 0 })).toBe('0');
    });
});

describe('formatCellValue', () => {
    it('returns empty string for null and undefined', () => {
        expect(formatCellValue(null)).toBe('');
        expect(formatCellValue(undefined)).toBe('');
    });

    it('JSON-stringifies plain objects', () => {
        expect(formatCellValue({ a: 1 })).toBe('{"a":1}');
    });

    it('stringifies primitives', () => {
        expect(formatCellValue('hi')).toBe('hi');
        expect(formatCellValue(3.5)).toBe('3.5');
        expect(formatCellValue(false)).toBe('false');
    });
});

describe('dateInputSegment', () => {
    it('extracts YYYY-MM-DD prefix', () => {
        expect(dateInputSegment('2024-01-15')).toBe('2024-01-15');
        expect(dateInputSegment('2024-01-15 12:00:00')).toBe('2024-01-15');
    });

    it('extracts from ISO with T separator', () => {
        expect(dateInputSegment('2024-06-01T08:30:00Z')).toBe('2024-06-01');
    });

    it('returns empty when no date prefix', () => {
        expect(dateInputSegment('not-a-date')).toBe('');
        expect(dateInputSegment('')).toBe('');
    });
});

describe('mergeCommittedDate', () => {
    it('returns empty when iso day is empty', () => {
        expect(mergeCommittedDate('', '2024-01-01')).toBe('');
    });

    it('replaces day and keeps time after space', () => {
        expect(mergeCommittedDate('2024-02-20', '2024-01-10 14:30:00')).toBe(
            '2024-02-20 14:30:00',
        );
    });

    it('replaces day and keeps time after T', () => {
        expect(mergeCommittedDate('2024-02-20', '2024-01-10T14:30:00')).toBe(
            '2024-02-20T14:30:00',
        );
    });

    it('returns iso day only when previous has no time portion', () => {
        expect(mergeCommittedDate('2024-03-01', '2024-01-01')).toBe(
            '2024-03-01',
        );
    });
});

describe('cellControlDomId', () => {
    it('joins sanitized row id and column id', () => {
        expect(cellControlDomId('row-1', 'name')).toBe('dt-row-1-name');
    });

    it('replaces unsafe characters in row id with hyphen', () => {
        expect(cellControlDomId('a/b:c', 'x')).toBe('dt-a-b-c-x');
    });
});

describe('columnEditableInDrawer', () => {
    const base = (
        over: Partial<FlatpackDataTableColumn>,
    ): FlatpackDataTableColumn => ({
        id: 'c',
        label: 'C',
        ...over,
    });

    it('returns false for actions', () => {
        expect(
            columnEditableInDrawer(
                base({ type: 'actions', buttons: {}, editable: true }),
            ),
        ).toBe(false);
    });

    it('returns true when editable is true', () => {
        expect(columnEditableInDrawer(base({ editable: true }))).toBe(true);
    });

    it('returns true for select with options', () => {
        expect(
            columnEditableInDrawer(
                base({
                    type: 'select',
                    options: [{ value: 'a', label: 'A' }],
                }),
            ),
        ).toBe(true);
    });

    it('returns true for badge with options', () => {
        expect(
            columnEditableInDrawer(
                base({
                    type: 'badge',
                    options: [{ value: 'a', label: 'A' }],
                }),
            ),
        ).toBe(true);
    });

    it('returns true for date', () => {
        expect(columnEditableInDrawer(base({ type: 'date' }))).toBe(true);
    });

    it('returns false for plain text column without editable', () => {
        expect(columnEditableInDrawer(base({}))).toBe(false);
    });

    it('returns false for select without options', () => {
        expect(columnEditableInDrawer(base({ type: 'select' }))).toBe(false);
    });
});
