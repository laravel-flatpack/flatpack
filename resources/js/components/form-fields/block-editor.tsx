import type { Value } from 'platejs';
import { BlockEditor } from '@/components/editor/block-editor';
import { Field, FieldContent, FieldDescription, FieldLabel } from '../ui/field';

export const BlockEditorField = ({
    id,
    label,
    placeholder,
    helperText,
    className,
    onValueChange,
}: {
    id: string;
    label: string;
    placeholder?: string;
    helperText?: string;
    className?: string;
    onValueChange?: (value: Value) => void;
}) => {
    return (
        <Field>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <FieldContent>
                <BlockEditor
                    id={id}
                    className={className}
                    placeholder={placeholder}
                    onValueChange={onValueChange}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
