import {
    normalizeColumnTruncate,
    parseImageColumnOptions,
} from '@/lib/data-table-utils';
import {
    BUTTON_VARIANT_UI_VALUES,
    FORM_FIELD_TYPES_CANONICAL,
    LIST_COLUMN_YAML_TYPES,
    LIST_FILTER_DATE_MODES,
    LIST_FILTER_TYPES,
    OPTION_STATUS_VALUES,
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
    FlatpackDataTableSelectOptionStatus,
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
            .map((col) => normalizeColumnRecord(col, ''))
            .filter((c) => c.id);
    }

    if (typeof columns !== 'object') {
        return [];
    }

    return Object.entries(columns as Record<string, unknown>)
        .map(([key, raw]) =>
            normalizeColumnRecord(
                raw !== null && typeof raw === 'object'
                    ? (raw as Record<string, unknown>)
                    : {},
                key,
            ),
        )
        .filter((c) => c.id);
}

function normalizeColumnRecord(
    col: Record<string, unknown>,
    fallbackId: string,
): FlatpackDataTableColumn {
    const {
        type: rawType,
        truncate: rawTruncate,
        edit_form_field: rawEditFormFieldSnake,
        editFormField: rawEditFormFieldCamel,
        actions: rawActions,
        options: rawOptions,
        relation_name: _rn,
        relation_value: _rv,
        relationName: _rnc,
        relationValue: _rvc,
        relation: _r,
        ...rest
    } = col;
    const id = String(col.id ?? fallbackId);
    const type = normalizeColumnType(rawType);
    const truncate = normalizeColumnTruncate(rawTruncate);
    const editFormField = normalizeColumnEditFormField(
        rawEditFormFieldSnake ?? rawEditFormFieldCamel,
    );
    const rel = pickRelationColumnFields(col);
    const actions = normalizeColumnActions(rawActions);

    let resolvedOptions: FlatpackDataTableColumn['options'] | undefined;

    if (Object.hasOwn(col, 'options')) {
        if (type === 'image') {
            const imageOpts = parseImageColumnOptions(rawOptions);
            if (
                imageOpts.width !== undefined ||
                imageOpts.height !== undefined ||
                imageOpts.aspect_ratio !== undefined
            ) {
                resolvedOptions = imageOpts;
            }
        } else {
            const optionsList = normalizeColumnOptions(rawOptions);
            if (optionsList.length > 0) {
                resolvedOptions = optionsList;
            }
        }
    }

    return {
        ...rest,
        id,
        ...(type !== undefined ? { type } : {}),
        ...(truncate !== undefined ? { truncate } : {}),
        ...(editFormField !== undefined ? { editFormField } : {}),
        ...(rel !== null ? rel : {}),
        ...(actions.length > 0 ? { actions } : {}),
        ...(resolvedOptions !== undefined ? { options: resolvedOptions } : {}),
    } as FlatpackDataTableColumn;
}

function normalizeColumnOptionObject(
    rec: Record<string, unknown>,
    fallbackValue: string,
): FlatpackDataTableColumnOption | null {
    const valueRaw = rec.value;
    const value =
        valueRaw != null && String(valueRaw).trim() !== ''
            ? String(valueRaw)
            : fallbackValue;
    const label = rec.label == null ? '' : String(rec.label);
    if (value === '' || label === '') {
        return null;
    }
    const out: FlatpackDataTableColumnOption = { value, label };
    const status = rec.status;
    if (
        typeof status === 'string' &&
        (OPTION_STATUS_VALUES as readonly string[]).includes(status)
    ) {
        out.status = status as FlatpackDataTableSelectOptionStatus;
    }
    if (typeof rec.icon === 'string' && rec.icon.trim() !== '') {
        out.icon = rec.icon.trim();
    }

    return out;
}

function normalizeColumnOptions(raw: unknown): FlatpackDataTableColumnOption[] {
    if (Array.isArray(raw)) {
        return raw
            .map((option) => {
                if (option == null || typeof option !== 'object') {
                    return null;
                }
                return normalizeColumnOptionObject(
                    option as Record<string, unknown>,
                    '',
                );
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
        .map(([mapKey, entry]) => {
            if (typeof entry === 'string') {
                return {
                    value: String(mapKey),
                    label: entry,
                };
            }
            if (entry != null && typeof entry === 'object') {
                return normalizeColumnOptionObject(
                    entry as Record<string, unknown>,
                    String(mapKey),
                );
            }

            return null;
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
    const items = Array.isArray(raw)
        ? raw
        : raw != null && typeof raw === 'object'
          ? Object.values(raw as Record<string, unknown>)
          : null;
    if (items == null) {
        return [];
    }
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
