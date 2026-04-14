import { type ComponentType, type LazyExoticComponent, lazy } from 'react';
import type { FormFieldType } from '@/types/form-fields';

function fieldComponentExportName(type: FormFieldType): string {
    return `${type
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('')}Field`;
}

const fieldModules = import.meta.glob('../components/form-fields/*.tsx');
type FormFieldModules = Record<string, () => Promise<unknown>>;

export function loadField(
    type: FormFieldType,
    modules: FormFieldModules = fieldModules,
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
