import type { FormFieldOutput, FormFieldProps } from '@/types/form-fields';

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

/** Inertia props for the `demo/components` page (`DemoController`). */
export type DemoComponentsInertiaProps = {
    /** Raw query string map from Laravel (`$request->query()`). */
    query: Record<string, unknown>;
    /** Field catalog from `Flatpack\Http\Controllers\DemoController` (was static in `demo.ts`). */
    catalog: DemoComponentCatalogEntry[];
};
