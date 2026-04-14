import { normalizeColumnTruncate } from '@/lib/data-table-utils';
import type {
    FlatpackDataTableColumn,
    FlatpackDataTableColumnOption,
    FlatpackDataTableFilter,
} from '@/types/data-table';

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

function normalizeColumnOptions(raw: unknown): FlatpackDataTableColumnOption[] {
    if (Array.isArray(raw)) {
        return raw
            .map((option) => {
                if (option == null || typeof option !== 'object') {
                    return null;
                }
                const rec = option as Record<string, unknown>;
                const value = rec.value == null ? '' : String(rec.value);
                const label = rec.label == null ? '' : String(rec.label);
                if (!value || !label) {
                    return null;
                }
                return { value, label };
            })
            .filter(
                (option): option is FlatpackDataTableColumnOption =>
                    option != null,
            );
    }
    if (raw == null || typeof raw !== 'object') {
        return [];
    }
    return Object.entries(raw as Record<string, unknown>)
        .map(([value, label]) => {
            if (typeof label !== 'string') {
                return null;
            }
            return {
                value: String(value),
                label,
            };
        })
        .filter(
            (option): option is FlatpackDataTableColumnOption => option != null,
        );
}

type FilterOverride = {
    label?: string;
    placeholder?: string;
    type?: 'select' | 'date';
    multiple?: boolean;
    mode?: 'exact' | 'from';
};

function normalizeFilterOverrides(raw: unknown): FilterOverride {
    if (raw == null || typeof raw !== 'object') {
        return {};
    }
    const rec = raw as Record<string, unknown>;
    const label =
        typeof rec.label === 'string' && rec.label.trim() !== ''
            ? rec.label.trim()
            : undefined;
    const placeholder =
        typeof rec.placeholder === 'string' && rec.placeholder.trim() !== ''
            ? rec.placeholder.trim()
            : undefined;
    const type =
        rec.type === 'select' || rec.type === 'date' ? rec.type : undefined;
    const multiple = rec.multiple === true ? true : undefined;
    const mode =
        rec.mode === 'from'
            ? 'from'
            : rec.mode === 'exact'
              ? 'exact'
              : undefined;
    return { label, placeholder, type, multiple, mode };
}

export function listYamlFiltersToDataTableFilters(
    columns: FlatpackDataTableColumn[],
    rawFilters: unknown,
): FlatpackDataTableFilter[] {
    if (rawFilters == null || typeof rawFilters !== 'object') {
        return [];
    }

    const filters = rawFilters as Record<string, unknown>;
    const byId = new Map(columns.map((column) => [column.id, column]));
    const out: FlatpackDataTableFilter[] = [];

    for (const [columnId, filterConfig] of Object.entries(filters)) {
        const column = byId.get(columnId);
        if (!column) {
            continue;
        }
        const overrides = normalizeFilterOverrides(filterConfig);

        const resolvedType = overrides.type ?? column.type;

        if (resolvedType === 'select') {
            const options = normalizeColumnOptions(column.options);
            if (options.length === 0) {
                continue;
            }
            out.push({
                id: column.id,
                label: overrides.label ?? column.label,
                placeholder: overrides.placeholder,
                type: 'select',
                multiple: overrides.multiple === true,
                options,
            });
            continue;
        }

        if (resolvedType === 'date') {
            out.push({
                id: column.id,
                label: overrides.label ?? column.label,
                placeholder: overrides.placeholder,
                type: 'date',
                mode: overrides.mode ?? 'exact',
            });
        }
    }

    return out;
}
