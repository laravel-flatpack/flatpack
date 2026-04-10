import type { Value } from 'platejs';
import { BlockEditor } from '@/components/editor/block-editor';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';

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
    const labelId = `${id}-label`;
    return (
        <Field>
            <FieldTitle id={labelId}>{label}</FieldTitle>
            <FieldContent>
                <BlockEditor
                    labelId={labelId}
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
