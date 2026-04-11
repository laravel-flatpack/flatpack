import type { FormFieldProps } from '@/types/form-fields';

export type RenderContext = {
    entryId: string;
    onValueChange: (value: unknown) => void;
};

export type FieldRenderFn = (
    props: FormFieldProps,
    ctx: RenderContext,
) => Record<string, unknown>;
