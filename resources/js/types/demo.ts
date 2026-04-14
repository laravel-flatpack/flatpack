import type { FormFieldProps } from '@/types/form-fields';

type DemoEntryBase = {
    id: string;
    title: string;
    description: string;
    value: unknown;
    showValue: boolean;
};

export type DemoComponentCatalogEntry = DemoEntryBase & {
    props: FormFieldProps;
};

export type DemoComponentType = FormFieldProps['type'];

export type DemoComponentsInertiaProps = {
    query: Record<string, unknown>;
    catalog: DemoComponentCatalogEntry[];
};
