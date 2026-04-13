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
        raw === 'actions'
    ) {
        return raw;
    }
    return undefined;
}

/**
 * Converts list composition `columns` from list.yaml into {@link FlatpackDataTableColumn} rows.
 * YAML may use either a map (`id: { label: … }`) or an array of column objects (demo style).
 */
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
                const { type: rawType, ...rest } = col;
                const id = String(col.id ?? '');
                const type = normalizeColumnType(rawType);
                return {
                    ...rest,
                    id,
                    ...(type !== undefined ? { type } : {}),
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
            const { type: rawType, ...rest } = col;
            const id = String(col.id ?? key);
            const type = normalizeColumnType(rawType);
            return {
                ...rest,
                id,
                ...(type !== undefined ? { type } : {}),
            } as FlatpackDataTableColumn;
        })
        .filter((c) => c.id);
}
