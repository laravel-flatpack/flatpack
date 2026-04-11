import type { Value } from 'platejs';
import { lazy, Suspense } from 'react';
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
    showFixedToolbar,
    onValueChange,
}: {
    id: string;
    label: string;
    placeholder?: string;
    helperText?: string;
    className?: string;
    showFixedToolbar?: boolean;
    onValueChange?: (value: Value) => void;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field>
            <FieldTitle id={labelId}>{label}</FieldTitle>
            <FieldContent>
                <Suspense fallback={<PlateEditorFallback />}>
                    <RichTextEditorLazy
                        labelId={labelId}
                        className={className}
                        placeholder={placeholder}
                        showFixedToolbar={showFixedToolbar}
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
