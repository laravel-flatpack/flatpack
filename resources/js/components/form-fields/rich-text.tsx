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
    toolbar,
    initialValue,
    onValueChange,
}: {
    id: string;
    label: string;
    placeholder?: string;
    helperText?: string;
    className?: string;
    toolbar?: boolean;
    initialValue?: Value;
    onValueChange?: (value: Value) => void;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field>
            <FieldTitle id={labelId}>{label}</FieldTitle>
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
