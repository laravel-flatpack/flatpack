import type { FormFieldOutput, FormFieldProps } from '@/types/form-fields';

type DemoEntryBase = {
    id: string;
    title: string;
    description: string;
    value: unknown;
    output?: FormFieldOutput;
};

export type DemoComponentCatalogEntry = DemoEntryBase & {
    props: FormFieldProps;
};

export type DemoComponentType = FormFieldProps['type'];

/** Inertia props for the `demo/components` page (`routes/demo.php`). */
export type DemoComponentsInertiaProps = {
    /** Raw query string map from Laravel (`$request->query()`). */
    query: Record<string, unknown>;
};
