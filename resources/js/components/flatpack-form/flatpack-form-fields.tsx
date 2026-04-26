import { useFlatpackEmbeddedTableToolbarAction } from '@/components/flatpack-form/flatpack-embedded-table-toolbar';
import { SchemaFieldsRenderer } from '@/components/form-fields/schema-fields-renderer';
import { fieldErrorMessages } from '@/lib/form-schema';
import {
    rowValidationFieldErrorsByStableId,
    rowValidationMessagesByStableId,
    tableFieldErrorState,
} from '@/lib/form-table-errors';
import { fieldIsRequired } from '@/lib/form-validation';
import type { FlatpackFormFieldsProps } from '@/types/flatpack-form-fields';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

export function FlatpackFormFields({
    entity,
    mode,
    record,
    fields,
    fieldComponents,
    fieldErrors,
    formValues,
    setFieldValue,
    onEmbeddedTableToolbarAction: onEmbeddedTableToolbarActionProp,
}: FlatpackFormFieldsProps) {
    const onEmbeddedTableToolbarActionFromContext =
        useFlatpackEmbeddedTableToolbarAction();
    const onEmbeddedTableToolbarAction =
        onEmbeddedTableToolbarActionProp ??
        onEmbeddedTableToolbarActionFromContext;
    const entries: SchemaFieldRenderEntry[] = fields.map(({ id, field }) => ({
        ...(field.type === 'table'
            ? (() => {
                  const errorState = tableFieldErrorState(fieldErrors, id);
                  return {
                      extraComponentProps: {
                          rowValidationMessagesById:
                              rowValidationMessagesByStableId(
                                  formValues[id],
                                  errorState,
                              ),
                          rowValidationFieldErrorsById:
                              rowValidationFieldErrorsByStableId(
                                  formValues[id],
                                  errorState,
                              ),
                      },
                  };
              })()
            : {}),
        id,
        field,
        value: formValues[id],
        onValueChange: (nextValue: unknown) =>
            setFieldValue(field, id, nextValue),
        required: fieldIsRequired(field),
        invalid: fieldErrorMessages(fieldErrors, id).length > 0,
        errors: fieldErrorMessages(fieldErrors, id),
    }));

    return (
        <SchemaFieldsRenderer
            entries={entries}
            entity={entity}
            parentRecordKey={record}
            modeKey={`${mode}:${record ?? 'new'}`}
            onEmbeddedTableToolbarAction={onEmbeddedTableToolbarAction}
            fieldComponents={fieldComponents}
            showErrors
        />
    );
}
