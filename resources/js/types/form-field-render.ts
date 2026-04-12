import type { FormFieldProps } from '@/types/form-fields';

/**
 * Per-field binding when mapping {@link FormFieldProps} to concrete field component props.
 * Used by the demo catalog, form page, list page, and any dynamic field renderer.
 */
export type FormFieldRenderContext = {
    /** Stable DOM id / field key (e.g. schema field name or catalog entry id). */
    fieldId: string;
    onValueChange: (value: unknown) => void;
};

export type FormFieldPropsMapper = (
    props: FormFieldProps,
    ctx: FormFieldRenderContext,
) => Record<string, unknown>;
