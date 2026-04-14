import { normalizeColumnTruncate } from '@/lib/data-table-utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

function normalizeColumnType(
    raw: unknown,
): FlatpackDataTableColumn['type'] | undefined {
    if (raw === 'datetime' || raw === 'date') {
        return 'date';
    }
    if (
        raw === 'text' ||
        raw === 'select' ||
        raw === 'badge' ||
        raw === 'actions' ||
        raw === 'relation'
    ) {
        return raw;
    }
    return undefined;
}

function pickRelationColumnFields(
    col: Record<string, unknown>,
): Pick<
    FlatpackDataTableColumn,
    'relation' | 'relationName' | 'relationValue'
> | null {
    const relation =
        typeof col.relation === 'string' ? col.relation.trim() : undefined;
    const relationName =
        typeof col.relation_name === 'string'
            ? col.relation_name.trim()
            : typeof col.relationName === 'string'
              ? col.relationName.trim()
              : undefined;
    const relationValue =
        typeof col.relation_value === 'string'
            ? col.relation_value.trim()
            : typeof col.relationValue === 'string'
              ? col.relationValue.trim()
              : undefined;
    if (!relation || !relationName || !relationValue) {
        return null;
    }
    return { relation, relationName, relationValue };
}

export function listYamlColumnsToDataTableColumns(
    columns: unknown,
): FlatpackDataTableColumn[] {
    if (columns == null) {
        return [];
    }

    if (Array.isArray(columns)) {
        return columns
            .filter(
                (c): c is Record<string, unknown> =>
                    c !== null && typeof c === 'object',
            )
            .map((col) => {
                const {
                    type: rawType,
                    truncate: rawTruncate,
                    relation_name: _rn,
                    relation_value: _rv,
                    relationName: _rnc,
                    relationValue: _rvc,
                    relation: _r,
                    ...rest
                } = col;
                const id = String(col.id ?? '');
                const type = normalizeColumnType(rawType);
                const truncate = normalizeColumnTruncate(rawTruncate);
                const rel = pickRelationColumnFields(
                    col as Record<string, unknown>,
                );
                return {
                    ...rest,
                    id,
                    ...(type !== undefined ? { type } : {}),
                    ...(truncate !== undefined ? { truncate } : {}),
                    ...(rel !== null ? rel : {}),
                } as FlatpackDataTableColumn;
            })
            .filter((c) => c.id);
    }

    if (typeof columns !== 'object') {
        return [];
    }

    return Object.entries(columns as Record<string, unknown>)
        .map(([key, raw]) => {
            const col =
                raw !== null && typeof raw === 'object'
                    ? (raw as Record<string, unknown>)
                    : {};
            const {
                type: rawType,
                truncate: rawTruncate,
                relation_name: _rn,
                relation_value: _rv,
                relationName: _rnc,
                relationValue: _rvc,
                relation: _r,
                ...rest
            } = col;
            const id = String(col.id ?? key);
            const type = normalizeColumnType(rawType);
            const truncate = normalizeColumnTruncate(rawTruncate);
            const rel = pickRelationColumnFields(
                col as Record<string, unknown>,
            );
            return {
                ...rest,
                id,
                ...(type !== undefined ? { type } : {}),
                ...(truncate !== undefined ? { truncate } : {}),
                ...(rel !== null ? rel : {}),
            } as FlatpackDataTableColumn;
        })
        .filter((c) => c.id);
}
