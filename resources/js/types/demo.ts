import type { FormFieldProps } from '@/types/form-fields';
import type {
    FlatpackCardWidget,
    FlatpackChartWidget,
    FlatpackMetricWidget,
    FlatpackStatusWidget,
    FlatpackTableWidget,
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
    props:
        | FlatpackMetricWidget
        | FlatpackCardWidget
        | FlatpackStatusWidget
        | FlatpackChartWidget
        | FlatpackTableWidget;
};

export type DemoComponentsCatalogId = 'all' | 'fields' | 'widgets';

export type DemoCatalogDocument = {
    id: DemoComponentsCatalogId;
    title: string;
    /** HTML string from the server; may include <code> for inline examples. */
    description: string | null;
    meta: { fieldCount: number; widgetCount: number };
    fields: DemoComponentCatalogEntry[];
    widgets: DemoComponentWidgetEntry[];
};

export type DemoComponentsInertiaProps = {
    catalogId: DemoComponentsCatalogId;
    query: Record<string, unknown>;
    document: DemoCatalogDocument;
};
