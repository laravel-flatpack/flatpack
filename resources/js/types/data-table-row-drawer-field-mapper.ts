import type { FormFieldProps } from '@/types/form-fields';

export type DrawerMappedField = {
    kind: 'form';
    field: Exclude<FormFieldProps, { type: 'table' }>;
};
