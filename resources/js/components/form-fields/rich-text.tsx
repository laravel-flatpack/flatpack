import type { Value } from 'platejs';
import { lazy, Suspense } from 'react';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import type { EditorFieldUploadConfig, FormFieldLabelShow } from '@/types/form-fields';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';
import { PlateEditorFallback } from './plate-editor-fallback';

const RichTextEditorLazy = lazy(async () => {
    const m = await import('@/components/editor/rich-text-editor');
    return { default: m.RichTextEditor };
});

export const RichTextField = ({
    id,
    label,
    placeholder,
    helperText,
    className,
    toolbar,
    upload,
    uploadEndpoint,
    uploadFieldId,
    initialValue,
    onValueChange,
    showLabel,
}: {
    id: string;
    label: string;
    placeholder?: string;
    helperText?: string;
    className?: string;
    toolbar?: boolean;
    upload?: EditorFieldUploadConfig;
    uploadEndpoint?: string;
    uploadFieldId?: string;
    initialValue?: Value;
    onValueChange?: (value: Value) => void;
    showLabel?: FormFieldLabelShow;
}) => {
    const labelId = `${id}-label`;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');
    return (
        <Field orientation={labelLayout.orientation}>
            <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                {label}
            </FieldTitle>
            <FieldContent>
                <Suspense
                    fallback={
                        <PlateEditorFallback
                            variant="rich-text"
                            toolbar={toolbar}
                            className={className}
                        />
                    }
                >
                    <RichTextEditorLazy
                        labelId={labelId}
                        className={className}
                        placeholder={placeholder}
                        toolbar={toolbar}
                        upload={upload}
                        uploadEndpoint={uploadEndpoint}
                        uploadFieldId={uploadFieldId}
                        initialValue={initialValue}
                        onValueChange={onValueChange}
                    />
                </Suspense>
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
