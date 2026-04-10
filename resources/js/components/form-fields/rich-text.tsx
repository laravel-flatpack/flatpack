import type { Value } from 'platejs';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';

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
                <RichTextEditor
                    labelId={labelId}
                    className={className}
                    placeholder={placeholder}
                    showFixedToolbar={showFixedToolbar}
                    onValueChange={onValueChange}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
