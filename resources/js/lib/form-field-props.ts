import {
    EMBEDDED_RELATION_DEFAULT_ACTIONS_COLUMN_ID,
    getDefaultRelationBulkActions,
    getDefaultRelationRowActions,
    getDefaultRelationToolbarActions,
    parseTableRelationType,
} from '@/lib/embedded-relation-table-defaults';
import { normalizeFormTableBulkActionsInput } from '@/lib/form-table-bulk-actions';
import { normalizeFormTableToolbarActionsInput } from '@/lib/form-table-toolbar-actions';
import { listYamlColumnsToDataTableColumns } from '@/lib/list-schema';
import { updateFormTableModelRow } from '@/lib/model-table-row-update';
import { route } from '@/lib/route';
import type { FlatpackDataTableColumn } from '@/types/data-table';
import type {
    FormFieldPropsMapper,
    FormFieldRenderContext,
} from '@/types/form-field-render';
import type { FormFieldProps, FormFieldType } from '@/types/form-fields';

export const RELATION_TABLE_TOOLBAR_DISABLED_TITLE =
    'Save the parent record before using these actions.' as const;

type NormalizedTableConfig = {
    columns: FlatpackDataTableColumn[];
    bulkActions: ReturnType<typeof normalizeFormTableBulkActionsInput>;
    toolbarActions: ReturnType<typeof normalizeFormTableToolbarActionsInput>;
};

const normalizedTableConfigBySource = new WeakMap<
    Record<string, unknown>,
    NormalizedTableConfig
>();

function resolveOpenDetailDrawerOnRowClick(
    raw: Extract<FormFieldProps, { type: 'table' }>,
): boolean {
    if (typeof raw.openDetailDrawerOnRowClick === 'boolean') {
        return raw.openDetailDrawerOnRowClick;
    }
    if (typeof raw.row_detail_drawer === 'boolean') {
        return raw.row_detail_drawer;
    }
    return true;
}

function normalizedOptions(
    options: unknown,
): Array<{ value: string; label: string; status?: unknown }> {
    if (!Array.isArray(options)) {
        return [];
    }

    return options.flatMap((option) => {
        if (typeof option !== 'object' || option === null) {
            return [];
        }

        const value = 'value' in option ? option.value : undefined;
        const label = 'label' in option ? option.label : undefined;

        if (typeof value !== 'string' || typeof label !== 'string') {
            return [];
        }

        return [
            {
                value,
                label,
                status: 'status' in option ? option.status : undefined,
            },
        ];
    });
}

function mapTextTextareaSelect(
    props: FormFieldProps,
    ctx: FormFieldRenderContext,
) {
    const {
        type: _t,
        preset: _preset,
        ...rest
    } = props as Extract<
        FormFieldProps,
        { type: 'text' | 'textarea' | 'select' }
    >;
    return {
        ...rest,
        id: ctx.fieldId,
        placeholder: rest.placeholder ?? '',
        options:
            props.type === 'select'
                ? normalizedOptions((rest as { options?: unknown }).options)
                : undefined,
        onValueChange: ctx.onValueChange,
    };
}

function mapCheckboxSwitch(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const {
        type: _t,
        helperText: _h,
        ...rest
    } = props as Extract<FormFieldProps, { type: 'checkbox' | 'switch' }>;
    return {
        ...rest,
        id: ctx.fieldId,
        onValueChange: ctx.onValueChange,
    };
}

function mapRichBlock(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const { type: _t, ...rest } = props as Extract<
        FormFieldProps,
        { type: 'rich-text' | 'block-editor' }
    >;
    return {
        ...rest,
        id: ctx.fieldId,
        onValueChange: ctx.onValueChange,
    };
}

function mapWidget(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'widget' }>;
    const { type: _t, ...rest } = p;
    return {
        ...rest,
        id: ctx.fieldId,
        widget: p.widget ?? {},
    };
}

function mapCombobox(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'combobox' }>;
    const options = normalizedOptions(p.options);
    return {
        id: ctx.fieldId,
        label: p.label,
        showLabel: p.showLabel,
        multiple: p.multiple ?? false,
        items: options.map((o) => ({
            value: o.value,
            label: o.label,
        })),
        singlePlaceholder: p.placeholder ?? '',
        multiPlaceholder: p.placeholder ?? '',
        singleDescription: p.helperText,
        multiDescription: p.helperText,
        onValueChange: ctx.onValueChange,
        useRelationRowPayload: Boolean(p.relation && p.multiple),
        relationValueKey: p.relation_value ?? 'id',
        relationLabelKey: p.relation_name,
        emitObject: p.emitObject === true,
    };
}

function mapDatePicker(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'date-picker' }>;
    return {
        id: ctx.fieldId,
        label: p.label,
        emptyLabel: p.placeholder ?? 'Pick a date',
        showLabel: p.showLabel,
        onValueChange: ctx.onValueChange,
    };
}

function mapDateRangePicker(
    props: FormFieldProps,
    ctx: FormFieldRenderContext,
) {
    const p = props as Extract<FormFieldProps, { type: 'date-range-picker' }>;
    return {
        id: ctx.fieldId,
        label: p.label,
        emptyLabel: p.placeholder ?? 'Pick a range',
        showLabel: p.showLabel,
        onValueChange: ctx.onValueChange,
    };
}

function mapTimePicker(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'time-picker' }>;
    return {
        id: ctx.fieldId,
        dateLabel: p.dateLabel ?? p.label,
        timeLabel: p.timeLabel ?? 'Time',
        dateEmptyLabel: p.datePlaceholder ?? p.placeholder ?? '',
        timeDefaultValue: p.timeDefaultValue ?? '09:00:00',
        showLabel: p.showLabel,
        onValueChange: ctx.onValueChange,
    };
}

function mapFileUpload(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const p = props as Extract<FormFieldProps, { type: 'file-upload' }>;
    const raw = p as Record<string, unknown>;
    const entity = typeof ctx.entity === 'string' ? ctx.entity.trim() : '';
    return {
        id: ctx.fieldId,
        label: p.label,
        showLabel: p.showLabel,
        helperText: p.helperText,
        mode: p.mode,
        multiple: p.multiple ?? false,
        maxFiles: raw.max_files ?? raw.maxFiles,
        maxSizeKb: raw.max_size_kb ?? raw.maxSizeKb,
        accept: p.accept,
        uploadEndpoint:
            entity !== '' ? route('flatpack.entities.upload', { entity }) : '',
        fieldId: ctx.fieldId,
        onValueChange: ctx.onValueChange,
    };
}

function hasEmbeddedTableToolbarKeySource(
    raw: Record<string, unknown>,
): boolean {
    return (
        Object.hasOwn(raw, 'actions') ||
        Object.hasOwn(raw, 'toolbar') ||
        Object.hasOwn(raw, 'toolbar_actions') ||
        Object.hasOwn(raw, 'toolbarActions')
    );
}

function hasEmbeddedTableBulkKeySource(raw: Record<string, unknown>): boolean {
    return (
        Object.hasOwn(raw, 'bulk_actions') || Object.hasOwn(raw, 'bulkActions')
    );
}

function mapTable(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const raw = props as Extract<FormFieldProps, { type: 'table' }>;
    const {
        type: _t,
        bulk_actions: _bulkSnake,
        bulkActions: _bulkCamel,
        actions: _actionsRaw,
        toolbar: _toolbarRaw,
        toolbar_actions: _stripToolbarSnake,
        toolbarActions: _stripToolbarCamel,
        columns: columnsRaw,
        row_detail_drawer: _rowDetailDrawerSnake,
        openDetailDrawerOnRowClick: _openDetailDrawerOnRowClickCamel,
        table_relation_type: _tableRelationType,
        ...rest
    } = raw;

    const rawObj = raw as Record<string, unknown>;
    const tableRelationType = parseTableRelationType(_tableRelationType);
    const relationName =
        typeof raw.relation === 'string' ? raw.relation.trim() : '';
    const relationBacked = relationName !== '';
    const modelClass = typeof raw.model === 'string' ? raw.model.trim() : '';
    const modelBacked = modelClass !== '' && !relationBacked;
    let normalizedTableConfig = normalizedTableConfigBySource.get(rawObj);
    if (normalizedTableConfig == null) {
        let columns: FlatpackDataTableColumn[] =
            listYamlColumnsToDataTableColumns(columnsRaw as unknown);

        let bulkActions = normalizeFormTableBulkActionsInput(rawObj);
        if (
            relationBacked &&
            !hasEmbeddedTableBulkKeySource(rawObj) &&
            bulkActions === undefined
        ) {
            bulkActions = getDefaultRelationBulkActions(tableRelationType);
        }
        let toolbarActions = normalizeFormTableToolbarActionsInput(
            raw.actions,
            raw.toolbar ?? raw.toolbar_actions ?? raw.toolbarActions,
        );
        if (
            relationBacked &&
            !hasEmbeddedTableToolbarKeySource(rawObj) &&
            toolbarActions === undefined
        ) {
            toolbarActions =
                getDefaultRelationToolbarActions(tableRelationType);
        }

        if (relationBacked && !columns.some((c) => c.type === 'actions')) {
            columns = [
                ...columns,
                {
                    id: EMBEDDED_RELATION_DEFAULT_ACTIONS_COLUMN_ID,
                    label: 'Actions',
                    type: 'actions',
                    actions: getDefaultRelationRowActions(tableRelationType),
                },
            ];
        }
        normalizedTableConfig = { columns, bulkActions, toolbarActions };
        normalizedTableConfigBySource.set(rawObj, normalizedTableConfig);
    }

    const { columns, bulkActions, toolbarActions } = normalizedTableConfig;
    const parentKey = ctx.parentRecordKey;
    const parentPersisted =
        parentKey !== undefined &&
        parentKey !== null &&
        String(parentKey).trim() !== '';
    const toolbarActionsDisabled =
        relationBacked &&
        toolbarActions !== undefined &&
        toolbarActions.length > 0 &&
        !parentPersisted;

    const onEmbeddedTableToolbarAction = ctx.onEmbeddedTableToolbarAction;
    const flatpackEntity =
        typeof ctx.entity === 'string' ? ctx.entity.trim() : '';
    const defaultSortRaw = rawObj.default_sort;
    const defaultSort =
        defaultSortRaw != null &&
        typeof defaultSortRaw === 'object' &&
        typeof (defaultSortRaw as { key?: unknown }).key === 'string' &&
        ((defaultSortRaw as { direction?: unknown }).direction === 'asc' ||
            (defaultSortRaw as { direction?: unknown }).direction === 'desc')
            ? {
                  key: ((defaultSortRaw as { key: string }).key ?? '').trim(),
                  direction: (defaultSortRaw as { direction: 'asc' | 'desc' })
                      .direction,
              }
            : undefined;
    const pagination =
        typeof rawObj.pagination === 'boolean' ? rawObj.pagination : undefined;
    const showColumnsVisibility =
        typeof rawObj.showColumnsVisibility === 'boolean'
            ? rawObj.showColumnsVisibility
            : undefined;

    return {
        ...rest,
        ...(bulkActions !== undefined ? { bulkActions } : {}),
        ...(toolbarActions !== undefined ? { toolbarActions } : {}),
        ...(toolbarActionsDisabled
            ? {
                  toolbarActionsDisabled: true,
                  toolbarActionsDisabledTitle:
                      RELATION_TABLE_TOOLBAR_DISABLED_TITLE,
              }
            : {}),
        columns,
        id: ctx.fieldId,
        flatpackTableFieldId: ctx.fieldId,
        ...(flatpackEntity !== '' ? { flatpackEntity } : {}),
        data: raw.data ?? [],
        onValueChange: ctx.onValueChange,
        onToolbarAction:
            onEmbeddedTableToolbarAction != null
                ? (actionId: string) =>
                      onEmbeddedTableToolbarAction({
                          fieldId: ctx.fieldId,
                          actionId,
                      })
                : undefined,
        rowDetailDrawer: true,
        openDetailDrawerOnRowClick: resolveOpenDetailDrawerOnRowClick(raw),
        onRowUpdate:
            modelBacked && flatpackEntity !== ''
                ? async ({
                      rowId,
                      row,
                  }: {
                      rowId: string;
                      row: Record<string, unknown>;
                  }) => {
                      const recordId = String(rowId ?? '').trim();
                      if (recordId === '') {
                          throw new Error('Row id is required');
                      }
                      return await updateFormTableModelRow({
                          entity: flatpackEntity,
                          fieldId: ctx.fieldId,
                          rowId: recordId,
                          values: row,
                      });
                  }
                : undefined,
        ...(defaultSort != null && defaultSort.key !== ''
            ? { defaultSort }
            : {}),
        ...(pagination !== undefined ? { pagination } : {}),
        ...(showColumnsVisibility !== undefined
            ? { showColumnsVisibility }
            : {}),
        ...(tableRelationType !== undefined ? { tableRelationType } : {}),
    };
}

function mapToolbar(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const {
        type: _t,
        actions,
        align,
        span,
        ...rest
    } = props as Extract<FormFieldProps, { type: 'toolbar' }>;

    return {
        ...rest,
        id: ctx.fieldId,
        actions,
        align: align ?? 'right',
        span: span ?? 'full',
    };
}

function mapRepeater(props: FormFieldProps, ctx: FormFieldRenderContext) {
    const { type: _t, ...rest } = props as Extract<
        FormFieldProps,
        { type: 'repeater' }
    >;
    const entity =
        typeof ctx.entity === 'string' && ctx.entity.trim() !== ''
            ? ctx.entity.trim()
            : undefined;
    return {
        ...rest,
        id: ctx.fieldId,
        onValueChange: ctx.onValueChange,
        ...(entity !== undefined ? { flatpackEntity: entity } : {}),
        ...(ctx.parentRecordKey !== undefined
            ? { parentRecordKey: ctx.parentRecordKey }
            : {}),
    };
}

const formFieldTypeToMapper: Record<FormFieldType, FormFieldPropsMapper> = {
    text: mapTextTextareaSelect,
    textarea: mapTextTextareaSelect,
    select: mapTextTextareaSelect,
    checkbox: mapCheckboxSwitch,
    switch: mapCheckboxSwitch,
    'rich-text': mapRichBlock,
    'block-editor': mapRichBlock,
    combobox: mapCombobox,
    'date-picker': mapDatePicker,
    'date-range-picker': mapDateRangePicker,
    'time-picker': mapTimePicker,
    'file-upload': mapFileUpload,
    table: mapTable,
    repeater: mapRepeater,
    toolbar: mapToolbar,
    widget: mapWidget,
};

/**
 * Maps canonical {@link FormFieldProps} (e.g. from PHP schema / demo catalog) to the prop
 * bags expected by components under `components/form-fields/*`.
 */
export function mapFormFieldPropsToComponentProps(
    props: FormFieldProps,
    context: FormFieldRenderContext,
): Record<string, unknown> {
    return formFieldTypeToMapper[props.type](props, context);
}
