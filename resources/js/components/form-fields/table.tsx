import { DataTable } from '@/components/table/data-table';
import type { FlatpackDataTableColumn } from '@/types/data-table';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';

export const TableField = ({
    id,
    label,
    helperText,
    columns,
    data,
    checkboxes,
    onValueChange: _onValueChange,
}: {
    id: string;
    label: string;
    helperText?: string;
    columns: FlatpackDataTableColumn[];
    data: Record<string, unknown>[];
    checkboxes?: boolean;
    onValueChange?: (value: unknown) => void;
}) => {
    const labelId = `${id}-label`;
    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <DataTable
                    id={id}
                    columns={columns}
                    data={data}
                    checkboxes={checkboxes}
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
