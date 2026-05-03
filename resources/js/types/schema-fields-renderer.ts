import type { ComponentType, LazyExoticComponent } from 'react';
import type { FieldSpanContext } from '@/lib/field-span';
import type { FormFieldProps } from '@/types/form-fields';

export type SchemaFieldError = { message: string };

export type SchemaFieldRenderEntry = {
    id: string;
    field: FormFieldProps;
    value: unknown;
    onValueChange: (nextSerializedValue: unknown) => void;
    required?: boolean;
    invalid?: boolean;
    disabled?: boolean;
    hidden?: boolean;
    errors?: SchemaFieldError[];
    /**
     * Extra props merged after mapped/component value props.
     * Use for context-specific behavior (e.g. drawer relation remote endpoint params).
     */
    extraComponentProps?: Record<string, unknown>;
    /**
     * Optional custom serializer for UI component values before `onValueChange`.
     * Defaults to `serializeFieldValue(field, nextValue)`.
     */
    serializeValue?: (
        field: FormFieldProps,
        nextValue: unknown,
        currentValue: unknown,
    ) => unknown;
};

export type SchemaFieldsRendererProps = {
    entries: SchemaFieldRenderEntry[];
    /** Grid layout context for {@link FormFieldProps.span} (default `page`). */
    spanContext?: FieldSpanContext;
    /**
     * Passed to {@code combobox} fields: Floating UI alignment for the options panel.
     * Use {@code end} in narrow columns (e.g. form sidebar) so the panel stays full min-width and aligns to the field’s trailing edge.
     */
    comboboxDropdownAlign?: 'start' | 'end';
    entity?: string;
    parentRecordKey?: string | null;
    modeKey?: string;
    onEmbeddedTableToolbarAction?: (args: {
        fieldId: string;
        actionId: string;
    }) => void;
    fieldComponents?: Record<
        string,
        LazyExoticComponent<ComponentType<Record<string, unknown>>>
    >;
    showErrors?: boolean;
};
