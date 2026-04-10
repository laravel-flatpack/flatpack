import type { Value } from 'platejs';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { Field, FieldContent, FieldDescription, FieldLabel } from '../ui/field';

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
    return (
        <Field>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <FieldContent>
                <RichTextEditor
                    id={id}
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
