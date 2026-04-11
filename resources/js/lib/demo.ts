import type { ComponentType, LazyExoticComponent } from 'react';
import { formFieldPropsToRenderProps } from '@/lib/demo-form-field-render';
import { mergeQueryOverridesIntoFormFieldProps } from '@/lib/demo-query';
import { loadField } from '@/lib/form';
import type {
    DemoComponentCatalogEntry,
    DemoComponentType,
} from '@/types/demo';
import type { FormFieldType } from '@/types/form-fields';

export {
    flattenDemoQuery,
    mergeQueryOverridesIntoFormFieldProps,
    parseLocationSearch,
    parseSearchParamsFromUrl,
    pickDemoComponentSelector,
} from '@/lib/demo-query';
export type { DemoComponentType };

export type DemoLazyFieldMap = Record<
    string,
    LazyExoticComponent<ComponentType<Record<string, unknown>>>
>;

/** Build `props.type` → entry map (server catalog order preserved in values). */
export function demoCatalogToByType(
    catalog: DemoComponentCatalogEntry[],
): Record<string, DemoComponentCatalogEntry> {
    return Object.fromEntries(catalog.map((e) => [e.props.type, e])) as Record<
        string,
        DemoComponentCatalogEntry
    >;
}

/** Ordered list of field `type` discriminants from the catalog. */
export function demoCatalogToTypes(
    catalog: DemoComponentCatalogEntry[],
): DemoComponentType[] {
    return catalog.map((e) => e.props.type);
}

export function normalizeDemoComponentType(
    raw: string | null,
    byType: Record<string, DemoComponentCatalogEntry>,
): DemoComponentType | null {
    if (raw === null || raw === '') {
        return null;
    }
    const lower = raw.trim().toLowerCase();
    if (Object.hasOwn(byType, lower)) {
        return lower as DemoComponentType;
    }
    return null;
}

/** Lazy field chunks for each distinct `props.type` in the catalog. */
export function lazyFieldMapFromCatalog(
    catalog: DemoComponentCatalogEntry[],
): DemoLazyFieldMap {
    const types = [...new Set(catalog.map((e) => e.props.type))];
    return Object.fromEntries(
        types.map((t) => [t, loadField(t as FormFieldType)]),
    ) as DemoLazyFieldMap;
}

export function buildDemoFieldRenderProps(
    entry: DemoComponentCatalogEntry,
    options: {
        queryOverrides: Record<string, string>;
        onValueChange: (value: unknown) => void;
    },
): Record<string, unknown> {
    const merged = mergeQueryOverridesIntoFormFieldProps(
        entry.props,
        options.queryOverrides,
    );
    return formFieldPropsToRenderProps(merged, {
        entryId: entry.id,
        onValueChange: options.onValueChange,
    });
}
