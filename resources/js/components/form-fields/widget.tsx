import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { WidgetsRenderer } from '@/components/widgets/widgets-renderer';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import type { FlatpackPageProps } from '@/types/flatpack';
import type { FormFieldLabelShow } from '@/types/form-fields';
import type { FlatpackFormPageProps } from '@/types/pages/flatpack';
import type { FlatpackWidget } from '@/types/widgets-composition';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function mergeEmbeddedWidgets(
    definitions: Record<string, Record<string, unknown>>,
    resolved: Record<string, Record<string, unknown>> | undefined | null,
): Record<string, FlatpackWidget> {
    const out: Record<string, FlatpackWidget> = {};
    for (const [wid, def] of Object.entries(definitions)) {
        const live = resolved != null ? resolved[wid] : undefined;
        if (isRecord(live)) {
            out[wid] = { ...def, ...live } as FlatpackWidget;
        } else {
            out[wid] = def as FlatpackWidget;
        }
    }
    return out;
}

export const WidgetField = ({
    id,
    label,
    helperText,
    showLabel,
    widget,
}: {
    id: string;
    label: string;
    helperText?: string;
    showLabel?: FormFieldLabelShow;
    widget: Record<string, Record<string, unknown>>;
}) => {
    const labelId = `${id}-label`;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');
    const { widgets: pageWidgets } = usePage<
        FlatpackFormPageProps & FlatpackPageProps
    >().props;

    const merged = useMemo(
        () => mergeEmbeddedWidgets(widget, pageWidgets),
        [widget, pageWidgets],
    );

    return (
        <Field orientation={labelLayout.orientation}>
            <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                {label}
            </FieldTitle>
            <FieldContent>
                <WidgetsRenderer
                    widgets={merged}
                    className="grid-cols-1 md:grid-cols-1 xl:grid-cols-1"
                />
                {helperText ? (
                    <FieldDescription>{helperText}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
