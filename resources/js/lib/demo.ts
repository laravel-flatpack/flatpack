import type { ComponentType, LazyExoticComponent } from 'react';
import {
    mergeQueryOverridesIntoFormFieldProps,
    pickDemoComponentSelector,
} from '@/lib/demo-query';
import { loadField } from '@/lib/form';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import type {
    DemoComponentCatalogEntry,
    DemoComponentType,
} from '@/types/demo';
import type { FormFieldProps, FormFieldType } from '@/types/form-fields';

export type { DemoComponentType };

export type DemoLazyFieldMap = Record<
    string,
    LazyExoticComponent<ComponentType<Record<string, unknown>>>
>;

export function demoCatalogToByType(
    catalog: { props: { type: string } }[],
): Record<string, DemoComponentCatalogEntry> {
    return Object.fromEntries(catalog.map((e) => [e.props.type, e])) as Record<
        string,
        DemoComponentCatalogEntry
    >;
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

export function resolveDemoComponentSelection(
    flatQuery: Record<string, string>,
    byType: Record<string, DemoComponentCatalogEntry>,
): {
    selectorRaw: string;
    normalized: DemoComponentType | null;
    requestedUnknown: boolean;
} {
    const selectorRaw = pickDemoComponentSelector(flatQuery);
    const selectorTrimmed = selectorRaw.trim().toLowerCase();
    const normalized =
        selectorTrimmed === ''
            ? null
            : normalizeDemoComponentType(selectorTrimmed, byType);
    const requestedUnknown = selectorTrimmed !== '' && normalized === null;
    return { selectorRaw, normalized, requestedUnknown };
}

export function lazyFieldMapFromCatalog(
    catalog: { props: { type: string } }[],
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
    const propsForField: FormFieldProps =
        merged.type === 'table'
            ? {
                  ...merged,
                  data: Array.isArray(entry.value)
                      ? (entry.value as Record<string, unknown>[])
                      : [],
              }
            : merged.type === 'select'
              ? { ...merged, value: entry.value }
              : merged;
    return mapFormFieldPropsToComponentProps(propsForField, {
        fieldId: entry.id,
        onValueChange: options.onValueChange,
        parentRecordKey: null,
    });
}
