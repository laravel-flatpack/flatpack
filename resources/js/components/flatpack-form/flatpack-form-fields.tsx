import type { ComponentType, LazyExoticComponent } from 'react';
import { Suspense } from 'react';
import { FieldError } from '@/components/ui/field';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import {
    componentValueProps,
    relationRemoteProps,
} from '@/lib/form-page-field-values';
import { type FormFieldEntry, fieldErrorMessages } from '@/lib/form-schema';
import { fieldIsRequired } from '@/lib/form-validation';
import type { FormFieldProps } from '@/types/form-fields';
import { Skeleton } from '../ui/skeleton';

type LazyFormField = LazyExoticComponent<
    ComponentType<Record<string, unknown>>
>;

type FlatpackFormFieldsProps = {
    entity: string;
    mode: 'create' | 'edit';
    record: string | null;
    fields: FormFieldEntry[];
    fieldComponents: Record<string, LazyFormField>;
    fieldErrors: Record<string, unknown>;
    formValues: Record<string, unknown>;
    setFieldValue: (
        field: FormFieldProps,
        fieldId: string,
        nextValue: unknown,
    ) => void;
};

const FieldLoading = (props: FormFieldProps) => {
    return (
        <>
            {props?.label && (
                <Skeleton className="mb-4 h-4 w-32 rounded-full" />
            )}
            {['textarea', 'rich-text', 'block-editor'].includes(props?.type) ? (
                <Skeleton className="h-25 w-full rounded-3xl" />
            ) : (
                <Skeleton className="h-9 rounded-3xl" />
            )}
            {props?.helperText && (
                <Skeleton className="h-4 w-40 rounded-full" />
            )}
        </>
    );
};

export function FlatpackFormFields({
    entity,
    mode,
    record,
    fields,
    fieldComponents,
    fieldErrors,
    formValues,
    setFieldValue,
}: FlatpackFormFieldsProps) {
    return (
        <>
            {fields.map(({ id, field }) => {
                const FieldComponent = fieldComponents[id];
                const componentProps = {
                    ...mapFormFieldPropsToComponentProps(field, {
                        fieldId: id,
                        onValueChange: (nextValue: unknown) =>
                            setFieldValue(field, id, nextValue),
                    }),
                    ...componentValueProps(field, formValues[id]),
                    ...relationRemoteProps(field, id, entity),
                    required: fieldIsRequired(field),
                    invalid: fieldErrorMessages(fieldErrors, id).length > 0,
                };

                return (
                    <div
                        key={`${id}:${mode}:${record ?? 'new'}`}
                        className="space-y-2"
                    >
                        <Suspense fallback={<FieldLoading {...field} />}>
                            <FieldComponent {...componentProps} />
                        </Suspense>
                        <FieldError
                            errors={fieldErrorMessages(fieldErrors, id)}
                        />
                    </div>
                );
            })}
        </>
    );
}
