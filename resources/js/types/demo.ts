import type { FormFieldProps } from '@/types/form-fields';
import type {
    FlatpackCardWidget,
    FlatpackMetricWidget,
} from '@/types/widgets-composition';

type DemoEntryBase = {
    id: string;
    title: string;
    description: string;
};

export type DemoComponentCatalogEntry = DemoEntryBase & {
    value: unknown;
    showValue: boolean;
    props: FormFieldProps;
};

export type DemoComponentType = FormFieldProps['type'];

export type DemoComponentWidgetEntry = DemoEntryBase & {
    props: FlatpackMetricWidget | FlatpackCardWidget;
};

export type DemoComponentsInertiaProps = {
    query: Record<string, unknown>;
    fieldsCatalog: DemoComponentCatalogEntry[];
    widgetsCatalog: DemoComponentWidgetEntry[];
};
