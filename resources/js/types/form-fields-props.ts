import type { ComponentType, LazyExoticComponent } from 'react';
import type {
    FlatpackFormTabPanelLayout,
    FormFieldEntry,
} from '@/lib/form-schema';
import type { FormFieldProps } from '@/types/form-fields';

export type LazyFormField = LazyExoticComponent<
    ComponentType<Record<string, unknown>>
>;

export type FormFieldsProps = {
    entity: string;
    mode: 'create' | 'edit';
    record: string | null;
    /** When set (from normalized `tab_panels`), fields render inside tab panels. */
    tabPanels?: FlatpackFormTabPanelLayout[];
    /**
     * When true with tab panels, fields not assigned to a tab render in place and the tab
     * strip + panels are rendered into `tabsPortalContainer` (e.g. full width below a sidebar row).
     */
    tabsFullWidthBelow?: boolean;
    /** Portal mount node for tab UI when `tabsFullWidthBelow` is true. */
    tabsPortalContainer?: HTMLElement | null;
    fields: FormFieldEntry[];
    fieldComponents: Record<string, LazyFormField>;
    fieldErrors: Record<string, unknown>;
    formValues: Record<string, unknown>;
    setFieldValue: (
        field: FormFieldProps,
        fieldId: string,
        nextValue: unknown,
    ) => void;
    /** Overrides context for custom embedded table toolbar (non draft-drawer) actions. */
    onEmbeddedTableToolbarAction?: (args: {
        fieldId: string;
        actionId: string;
    }) => void;
    /** Narrow layouts (e.g. sidebar): right-align combobox option panels to the field. */
    comboboxDropdownAlign?: 'start' | 'end';
};
