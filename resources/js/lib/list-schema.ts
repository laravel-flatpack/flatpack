import { normalizeColumnTruncate } from '@/lib/data-table-utils';
import {
    BUTTON_VARIANT_UI_VALUES,
    FORM_FIELD_TYPES_CANONICAL,
    LIST_COLUMN_YAML_TYPES,
    LIST_FILTER_DATE_MODES,
    LIST_FILTER_TYPES,
    SUCCESS_REDIRECT_VALUES,
} from '@/lib/generated/composition-schema-keys';
import type {
    FlatpackActionVariant,
    FlatpackDataTableActionButton,
    FlatpackDataTableColumn,
    FlatpackDataTableColumnOption,
    FlatpackDataTableEditFormField,
    FlatpackDataTableEditFormFieldType,
    FlatpackDataTableFilter,
    FlatpackDataTableFilterDateMode,
    FlatpackDataTableFilterType,
    FlatpackSuccessRedirect,
} from '@/types/data-table';

const listColumnYamlSet = new Set<string>(LIST_COLUMN_YAML_TYPES);
const filterTypesSet = new Set<string>(LIST_FILTER_TYPES);
const filterDateModesSet = new Set<string>(LIST_FILTER_DATE_MODES);
const buttonVariantUiSet = new Set<string>(BUTTON_VARIANT_UI_VALUES);

function normalizeSuccessRedirect(
    raw: unknown,
): FlatpackSuccessRedirect | undefined {
    if (raw === true) {
        return 'list';
    }
    if (typeof raw !== 'string') {
        return undefined;
    }
    const v = raw.trim();
    if (v.toLowerCase() === 'true') {
        return 'list';
    }
    return (SUCCESS_REDIRECT_VALUES as readonly string[]).includes(v)
        ? (v as FlatpackSuccessRedirect)
        : undefined;
}

function normalizeColumnType(
    raw: unknown,
): FlatpackDataTableColumn['type'] | undefined {
    if (typeof raw !== 'string') {
        return undefined;
    }
    const r = raw.trim();
    if (r === 'datetime' || r === 'date') {
        return 'date';
    }
    if (!listColumnYamlSet.has(r)) {
        return undefined;
    }
    return r as NonNullable<FlatpackDataTableColumn['type']>;
}

function normalizeColumnEditFormField(
    raw: unknown,
): FlatpackDataTableEditFormField | undefined {
    if (raw == null || typeof raw !== 'object') {
        return undefined;
    }
    const rec = raw as Record<string, unknown>;
    const type = typeof rec.type === 'string' ? rec.type.trim() : '';
    if (
        type === '' ||
        !(FORM_FIELD_TYPES_CANONICAL as readonly string[]).includes(type)
    ) {
        return undefined;
    }
    return {
        ...rec,
        type: type as FlatpackDataTableEditFormFieldType,
    };
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
                    edit_form_field: rawEditFormFieldSnake,
                    editFormField: rawEditFormFieldCamel,
                    actions: rawActions,
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
                const editFormField = normalizeColumnEditFormField(
                    rawEditFormFieldSnake ?? rawEditFormFieldCamel,
                );
                const rel = pickRelationColumnFields(
                    col as Record<string, unknown>,
                );
                const actions = normalizeColumnActions(rawActions);
                return {
                    ...rest,
                    id,
                    ...(type !== undefined ? { type } : {}),
                    ...(truncate !== undefined ? { truncate } : {}),
                    ...(editFormField !== undefined ? { editFormField } : {}),
                    ...(rel !== null ? rel : {}),
                    ...(actions.length > 0 ? { actions } : {}),
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
                edit_form_field: rawEditFormFieldSnake,
                editFormField: rawEditFormFieldCamel,
                actions: rawActions,
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
            const editFormField = normalizeColumnEditFormField(
                rawEditFormFieldSnake ?? rawEditFormFieldCamel,
            );
            const rel = pickRelationColumnFields(
                col as Record<string, unknown>,
            );
            const actions = normalizeColumnActions(rawActions);
            return {
                ...rest,
                id,
                ...(type !== undefined ? { type } : {}),
                ...(truncate !== undefined ? { truncate } : {}),
                ...(editFormField !== undefined ? { editFormField } : {}),
                ...(rel !== null ? rel : {}),
                ...(actions.length > 0 ? { actions } : {}),
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

function normalizeActionVariant(raw: unknown): FlatpackActionVariant {
    if (typeof raw !== 'string') {
        return 'outline';
    }
    const value = raw.trim();
    if (value === 'primary') {
        return 'default';
    }
    if (buttonVariantUiSet.has(value)) {
        return value as FlatpackActionVariant;
    }
    return 'outline';
}

/** Normalizes list column row actions from YAML into a button list. */
export function normalizeColumnActions(
    raw: unknown,
): FlatpackDataTableActionButton[] {
    if (!Array.isArray(raw)) {
        return [];
    }
    const items = raw;
    const normalized = items.map((action) => {
        if (action == null || typeof action !== 'object') {
            return null;
        }
        const rec = action as Record<string, unknown>;
        const label = typeof rec.label === 'string' ? rec.label.trim() : '';
        if (label === '') {
            return null;
        }
        const actionName =
            typeof rec.action === 'string' ? rec.action.trim() : '';
        const href = typeof rec.href === 'string' ? rec.href.trim() : '';
        if ((actionName === '' && href === '') || (actionName && href)) {
            return null;
        }
        const icon = typeof rec.icon === 'string' ? rec.icon.trim() : '';
        const successRedirect = normalizeSuccessRedirect(rec.success_redirect);
        const confirm = rec.confirm === true ? true : undefined;
        const successMessage =
            typeof rec.success_message === 'string' &&
            rec.success_message.trim() !== ''
                ? rec.success_message.trim()
                : undefined;
        return {
            label,
            ...(icon !== '' ? { icon } : {}),
            ...(actionName !== '' ? { action: actionName } : {}),
            ...(href !== '' ? { href } : {}),
            variant: normalizeActionVariant(rec.variant),
            ...(successRedirect !== undefined
                ? { success_redirect: successRedirect }
                : {}),
            ...(confirm === true ? { confirm: true } : {}),
            ...(successMessage !== undefined
                ? { success_message: successMessage }
                : {}),
        } satisfies FlatpackDataTableActionButton;
    });

    return normalized.filter(
        (action): action is NonNullable<(typeof normalized)[number]> =>
            action !== null,
    );
}

type FilterOverride = {
    label?: string;
    placeholder?: string;
    type?: FlatpackDataTableFilterType;
    multiple?: boolean;
    mode?: FlatpackDataTableFilterDateMode;
    options?: FlatpackDataTableColumnOption[];
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
        typeof rec.type === 'string' && filterTypesSet.has(rec.type)
            ? (rec.type as FlatpackDataTableFilterType)
            : undefined;
    const multiple = rec.multiple === true ? true : undefined;
    const mode =
        typeof rec.mode === 'string' && filterDateModesSet.has(rec.mode)
            ? (rec.mode as FlatpackDataTableFilterDateMode)
            : undefined;
    const options = Object.hasOwn(rec, 'options')
        ? normalizeColumnOptions(rec.options)
        : undefined;
    return { label, placeholder, type, multiple, mode, options };
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
            const options =
                overrides.options ?? normalizeColumnOptions(column.options);
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
