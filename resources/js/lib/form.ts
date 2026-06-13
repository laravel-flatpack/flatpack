import { type ComponentType, type LazyExoticComponent, lazy } from 'react';
import type { FormFieldType } from '@/types/form-fields';

const fieldModules = import.meta.glob([
    '../components/form-fields/*.tsx',
    '!../components/form-fields/*.test.tsx',
    '!../components/form-fields/schema-fields-renderer.tsx',
]);

function fieldComponentExportName(type: FormFieldType): string {
    return `${type
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('')}Field`;
}

type FormFieldModules = Record<string, () => Promise<unknown>>;

/** One lazy component per type so remounts (e.g. after Inertia save) do not re-trigger Suspense fallbacks. */
const lazyFieldByType = new Map<
    FormFieldType,
    LazyExoticComponent<ComponentType<Record<string, unknown>>>
>();

function createLazyField(
    type: FormFieldType,
    modules: FormFieldModules,
): LazyExoticComponent<ComponentType<Record<string, unknown>>> {
    const path = `../components/form-fields/${type}.tsx`;
    return lazy(async () => {
        const load = modules[path];
        if (load === undefined) {
            throw new Error(
                `loadField: missing module for type "${type}" (${path})`,
            );
        }
        const m = (await load()) as Record<
            string,
            ComponentType<Record<string, unknown>>
        >;
        const exportName = fieldComponentExportName(type);
        const Comp = m[exportName];
        if (typeof Comp !== 'function') {
            throw new Error(
                `loadField: expected "${exportName}" export in form-fields/${type}.tsx`,
            );
        }
        return { default: Comp };
    });
}

export function loadField(
    type: FormFieldType,
    modules: FormFieldModules = fieldModules,
): LazyExoticComponent<ComponentType<Record<string, unknown>>> {
    if (modules === fieldModules) {
        let cached = lazyFieldByType.get(type);
        if (cached === undefined) {
            cached = createLazyField(type, modules);
            lazyFieldByType.set(type, cached);
        }
        return cached;
    }
    return createLazyField(type, modules);
}
